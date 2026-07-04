<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusCompletenessPlugin\Message\Command\RefreshCompletenessRollups;
use Setono\SyliusCompletenessPlugin\Model\CompletenessContext;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRule;
use Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManagerInterface;
use Setono\SyliusCompletenessPlugin\Tests\Application\Entity\Product;
use Sylius\Component\Core\Model\ProductImage;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

/**
 * Exercises the recalculation triggers against a real database: an ordinary flush marks the affected
 * product dirty (via onFlush recompute, no dispatch), a rule change bumps the rubric version, a
 * context change dispatches a rollup refresh, and the drain command recalculates + clears the flag
 *
 * @group functional
 */
final class RecalculationTriggerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    /** @var list<object> */
    private array $entitiesToRemove = [];

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->entityManager = $entityManager;
    }

    protected function tearDown(): void
    {
        try {
            if ($this->entityManager->isOpen()) {
                foreach (array_reverse($this->entitiesToRemove) as $entity) {
                    if ($this->entityManager->contains($entity)) {
                        $this->entityManager->remove($entity);
                    }
                }
                $this->entityManager->flush();
            }
        } catch (\Throwable) {
            // best effort cleanup - never mask the actual test result
        } finally {
            $this->entitiesToRemove = [];
        }

        parent::tearDown();
    }

    private function getTransport(): InMemoryTransport
    {
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.setono_sylius_completeness');

        return $transport;
    }

    private function createProduct(): Product
    {
        $product = new Product();
        $product->setCode(uniqid('completeness_test_', true));
        $product->setCurrentLocale('en_US');
        $product->setFallbackLocale('en_US');
        $product->setName('Completeness test product');
        $product->setSlug(uniqid('completeness-test-', true));

        $this->entityManager->persist($product);
        $this->entityManager->flush();
        $this->entitiesToRemove[] = $product;

        return $product;
    }

    private function runDrain(): void
    {
        $command = (new Application(self::$kernel))->find('setono:completeness:process');
        (new CommandTester($command))->execute([]);
    }

    /**
     * @test
     */
    public function an_update_touching_a_product_and_its_children_marks_it_dirty_via_recompute(): void
    {
        $product = $this->createProduct();
        $product->setCompletenessDirtyAt(null);
        $this->entityManager->flush();

        // one flush changing the translation AND adding an image - the product must come out dirty
        $product->setName('Renamed completeness test product');
        $image = new ProductImage();
        $image->setPath('foo.jpg');
        $product->addImage($image);

        $this->entityManager->flush();

        // re-read from the database to prove the flag was written as part of that same flush
        $productId = $product->getId();
        $this->entityManager->clear();
        $reloaded = $this->entityManager->find(Product::class, $productId);
        self::assertInstanceOf(Product::class, $reloaded);
        self::assertNotNull($reloaded->getCompletenessDirtyAt());
    }

    /**
     * @test
     */
    public function a_rule_change_bumps_the_rubric_version_without_dispatching(): void
    {
        /** @var RubricVersionManagerInterface $rubricVersionManager */
        $rubricVersionManager = self::getContainer()->get(\Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManager::class);
        $versionBefore = $rubricVersionManager->getCurrentVersion();

        $rule = new CompletenessRule();
        $rule->setCode(uniqid('rule_', true));
        $rule->setLabel('Has name');
        $rule->setType('has_name');

        $this->getTransport()->reset();

        $this->entityManager->persist($rule);
        $this->entityManager->flush();
        $this->entitiesToRemove[] = $rule;

        self::assertSame([], $this->getTransport()->getSent());
        self::assertSame($versionBefore + 1, $rubricVersionManager->getCurrentVersion());
    }

    /**
     * @test
     */
    public function a_context_change_dispatches_a_rollup_refresh(): void
    {
        $context = new CompletenessContext();
        $context->setChannelCode(uniqid('CH_', true));
        $context->setLocaleCode('en_US');

        $this->getTransport()->reset();

        $this->entityManager->persist($context);
        $this->entityManager->flush();
        $this->entitiesToRemove[] = $context;

        $sent = $this->getTransport()->getSent();
        self::assertCount(1, $sent);
        self::assertInstanceOf(RefreshCompletenessRollups::class, $sent[0]->getMessage());
    }

    /**
     * @test
     */
    public function the_drain_scores_a_new_product_and_clears_the_dirty_flag_afterwards(): void
    {
        $product = $this->createProduct();

        // a brand-new product has a null rubric version, so the drain picks it up and scores it
        $this->runDrain();

        $productId = $product->getId();
        $this->entityManager->clear();
        $reloaded = $this->entityManager->find(Product::class, $productId);
        self::assertInstanceOf(Product::class, $reloaded);
        self::assertNotNull($reloaded->getCompletenessRubricVersion());
        self::assertNull($reloaded->getCompletenessDirtyAt());

        // now dirty it through an update and prove the drain clears the flag
        $reloaded->setName('Changed');
        $this->entityManager->flush();
        self::assertNotNull($reloaded->getCompletenessDirtyAt());

        $this->runDrain();

        $this->entityManager->clear();
        $again = $this->entityManager->find(Product::class, $productId);
        self::assertInstanceOf(Product::class, $again);
        self::assertNull($again->getCompletenessDirtyAt());
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusCompletenessPlugin\Message\Command\RecalculateAllProductsCompleteness;
use Setono\SyliusCompletenessPlugin\Message\Command\RecalculateProductCompleteness;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRule;
use Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManagerInterface;
use Setono\SyliusCompletenessPlugin\Tests\Application\Entity\Product;
use Sylius\Component\Core\Model\ProductImage;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

/**
 * Proves the single-capture-point promise of the onFlush pipeline against a real database:
 * one flush touching a product and several of its child entities produces exactly ONE
 * recalculation message, and a rule change produces a catalog-wide recalculation + version bump
 *
 * @group functional
 */
final class RecalculateOnFlushTest extends KernelTestCase
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
        $this->removeCreatedEntities();

        parent::tearDown();
    }

    private function removeCreatedEntities(): void
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
    }

    private function getTransport(): InMemoryTransport
    {
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.setono_sylius_completeness');

        return $transport;
    }

    /**
     * @test
     */
    public function a_flush_touching_a_product_and_its_children_produces_exactly_one_recalculation(): void
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

        $this->getTransport()->reset();

        // one flush: change the translation AND add an image
        $product->setName('Renamed completeness test product');
        $image = new ProductImage();
        $image->setPath('foo.jpg');
        $product->addImage($image);

        $this->entityManager->flush();

        $envelopes = $this->getTransport()->getSent();
        self::assertCount(1, $envelopes);

        $message = $envelopes[0]->getMessage();
        self::assertInstanceOf(RecalculateProductCompleteness::class, $message);
        self::assertSame($product->getId(), $message->productId);
    }

    /**
     * @test
     */
    public function a_rule_change_bumps_the_rubric_version_and_recalculates_the_catalog(): void
    {
        /** @var RubricVersionManagerInterface $rubricVersionManager */
        $rubricVersionManager = self::getContainer()->get('setono_sylius_completeness.rubric_version_manager');
        $versionBefore = $rubricVersionManager->getCurrentVersion();

        $rule = new CompletenessRule();
        $rule->setCode(uniqid('rule_', true));
        $rule->setLabel('Has name');
        $rule->setType('has_name');

        $this->getTransport()->reset();

        $this->entityManager->persist($rule);
        $this->entityManager->flush();
        $this->entitiesToRemove[] = $rule;

        $envelopes = $this->getTransport()->getSent();
        self::assertCount(1, $envelopes);
        self::assertInstanceOf(RecalculateAllProductsCompleteness::class, $envelopes[0]->getMessage());

        self::assertSame($versionBefore + 1, $rubricVersionManager->getCurrentVersion());
    }
}

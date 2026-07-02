<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusCompletenessPlugin\Message\Command\RecalculateProductCompleteness;
use Setono\SyliusCompletenessPlugin\Tests\Application\Entity\Product;
use Sylius\Component\Core\Model\AdminUser;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Locale\Model\Locale;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @group functional
 */
final class ProductCompletenessDisplayTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    /** @var list<object> */
    private array $entitiesToRemove = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = self::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->entityManager = $entityManager;

        $admin = new AdminUser();
        $admin->setUsername(uniqid('completeness_admin_', true));
        $admin->setEmail(uniqid('completeness_admin_', true) . '@example.com');
        $admin->setPlainPassword('password');
        $admin->setLocaleCode('en_US');
        $admin->setEnabled(true);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();
        $this->entitiesToRemove[] = $admin;

        $this->client->loginUser(self::toSecurityUser($admin), 'admin');
    }

    private static function toSecurityUser(object $user): UserInterface
    {
        if (!$user instanceof UserInterface) {
            throw new \LogicException('The admin user does not implement the Symfony security UserInterface');
        }

        return $user;
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
            // best effort cleanup
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
        $locale = $this->entityManager->getRepository(Locale::class)->findOneBy(['code' => 'en_US']);
        if (null === $locale) {
            $locale = new Locale();
            $locale->setCode('en_US');
            $this->entityManager->persist($locale);
            $this->entitiesToRemove[] = $locale;
        }

        $currency = $this->entityManager->getRepository(Currency::class)->findOneBy(['code' => 'USD']);
        if (null === $currency) {
            $currency = new Currency();
            $currency->setCode('USD');
            $this->entityManager->persist($currency);
            $this->entitiesToRemove[] = $currency;
        }

        $channel = new Channel();
        $channel->setCode(uniqid('COMPLETENESS_', true));
        $channel->setName('Completeness display test channel');
        $channel->setTaxCalculationStrategy('order_items_based');
        $channel->setBaseCurrency($currency);
        $channel->setDefaultLocale($locale);
        $channel->addLocale($locale);
        $this->entityManager->persist($channel);
        $this->entitiesToRemove[] = $channel;

        $product = new Product();
        $product->setCode(uniqid('completeness_display_', true));
        $product->setCurrentLocale('en_US');
        $product->setFallbackLocale('en_US');
        $product->setName('Completeness display product');
        $product->setSlug(uniqid('completeness-display-', true));
        $product->addChannel($channel);
        $this->entityManager->persist($product);
        $this->entityManager->flush();
        $this->entitiesToRemove[] = $product;

        return $product;
    }

    /**
     * @test
     */
    public function the_product_grid_renders_the_completeness_column(): void
    {
        $crawler = $this->client->request('GET', '/admin/products/');

        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $crawler->filterXPath('//th[contains(., "Completeness")]')->count());
    }

    /**
     * @test
     */
    public function recalculate_now_persists_the_score_synchronously(): void
    {
        $product = $this->createProduct();

        $crawler = $this->client->request('GET', sprintf('/admin/products/%d', $product->getId()));
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[action$="/completeness/recalculate"]')->form();
        $this->client->submit($form);

        self::assertResponseRedirects();

        $this->entityManager->clear();
        /** @var Product $reloaded */
        $reloaded = $this->entityManager->find(Product::class, $product->getId());
        self::assertNotNull($reloaded->getCompletenessRubricVersion());
        self::assertFalse($reloaded->getCompletenesses()->isEmpty());
    }

    /**
     * @test
     */
    public function bulk_recalculate_dispatches_a_message_per_selected_product(): void
    {
        $first = $this->createProduct();
        $second = $this->createProduct();

        $crawler = $this->client->request('GET', '/admin/products/');
        $token = $crawler->filter('form[action$="/completeness/bulk-recalculate"] input[name="_csrf_token"]')->attr('value');

        $this->getTransport()->reset();

        $this->client->request('POST', '/admin/products/completeness/bulk-recalculate', [
            'ids' => [$first->getId(), $second->getId()],
            '_csrf_token' => $token,
        ]);

        self::assertResponseRedirects();

        $messages = array_filter(
            array_map(static fn ($envelope) => $envelope->getMessage(), $this->getTransport()->getSent()),
            static fn (object $message): bool => $message instanceof RecalculateProductCompleteness,
        );
        self::assertCount(2, $messages);
    }
}

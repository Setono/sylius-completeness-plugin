<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusCompletenessPlugin\Message\Command\RecalculateAllProductsCompleteness;
use Sylius\Component\Core\Model\AdminUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @group functional
 */
final class CompletenessDashboardTest extends WebTestCase
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

    /**
     * @test
     */
    public function the_dashboard_renders_with_the_catalog_figures(): void
    {
        $crawler = $this->client->request('GET', '/admin/completeness/dashboard');

        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $crawler->filterXPath('//div[contains(@class, "statistic")]')->count());
    }

    /**
     * @test
     */
    public function it_dispatches_a_catalog_recalculation(): void
    {
        $crawler = $this->client->request('GET', '/admin/completeness/dashboard');
        $token = $crawler->filter('form[action$="/completeness/recalculate-all"] input[name="_csrf_token"]')->attr('value');

        $this->getTransport()->reset();

        $this->client->request('POST', '/admin/completeness/recalculate-all', ['_csrf_token' => $token]);

        self::assertResponseRedirects();

        $messages = array_filter(
            array_map(static fn ($envelope) => $envelope->getMessage(), $this->getTransport()->getSent()),
            static fn (object $message): bool => $message instanceof RecalculateAllProductsCompleteness,
        );
        self::assertCount(1, $messages);
    }
}

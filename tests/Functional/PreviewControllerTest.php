<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\AdminUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @group functional
 */
final class PreviewControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = self::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->entityManager = $entityManager;

        $this->admin = new AdminUser();
        $this->admin->setUsername(uniqid('completeness_admin_', true));
        $this->admin->setEmail(uniqid('completeness_admin_', true) . '@example.com');
        $this->admin->setPlainPassword('password');
        $this->admin->setLocaleCode('en_US');
        $this->admin->setEnabled(true);

        $this->entityManager->persist($this->admin);
        $this->entityManager->flush();

        $this->client->loginUser(self::toSecurityUser($this->admin), 'admin');
    }

    protected function tearDown(): void
    {
        try {
            if ($this->entityManager->isOpen()) {
                $managed = $this->entityManager->find(AdminUser::class, $this->admin->getId());
                if (null !== $managed) {
                    $this->entityManager->remove($managed);
                    $this->entityManager->flush();
                }
            }
        } catch (\Throwable) {
            // best effort cleanup
        }

        parent::tearDown();
    }

    private static function toSecurityUser(object $user): UserInterface
    {
        if (!$user instanceof UserInterface) {
            throw new \LogicException('The admin user does not implement the Symfony security UserInterface');
        }

        return $user;
    }

    /**
     * @test
     */
    public function the_preview_screen_renders(): void
    {
        $crawler = $this->client->request('GET', '/admin/completeness/preview');

        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $crawler->filter('form[name="setono_sylius_completeness_preview"]')->count());
    }
}

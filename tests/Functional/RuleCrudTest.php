<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusCompletenessPlugin\Model\CompletenessContextSetting;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRule;
use Sylius\Component\Core\Model\AdminUser;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Locale\Model\Locale;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Smoke tests for both admin CRUDs: the grids render, valid submissions persist
 * and expression validation rejects broken expressions at save time
 *
 * @group functional
 */
final class RuleCrudTest extends WebTestCase
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

    /**
     * Sylius' user interface only extends the Symfony security UserInterface through the
     * sylius-labs/polyfill-symfony-security class alias which static analysis cannot see,
     * hence this runtime checked conversion
     */
    private static function toSecurityUser(object $user): UserInterface
    {
        if (!$user instanceof UserInterface) {
            throw new \LogicException('The admin user does not implement the Symfony security UserInterface');
        }

        return $user;
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

    /**
     * @test
     */
    public function the_rule_grid_and_create_form_render(): void
    {
        $this->client->request('GET', '/admin/completeness-rules/');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/admin/completeness-rules/new');
        self::assertResponseIsSuccessful();
    }

    /**
     * @test
     */
    public function a_valid_rule_is_created(): void
    {
        $this->client->request('GET', '/admin/completeness-rules/new');

        $this->client->submitForm('Create', [
            'setono_sylius_completeness_completeness_rule[label]' => 'Has a name (functional test)',
            'setono_sylius_completeness_completeness_rule[type]' => 'has_name',
            'setono_sylius_completeness_completeness_rule[weightTier]' => 'critical',
            'setono_sylius_completeness_completeness_rule[position]' => '0',
        ]);

        self::assertResponseRedirects();

        $rule = $this->entityManager->getRepository(CompletenessRule::class)->findOneBy(['code' => 'has_a_name_functional_test']);
        self::assertInstanceOf(CompletenessRule::class, $rule);
        self::assertSame('has_name', $rule->getType());
        $this->entitiesToRemove[] = $rule;
    }

    /**
     * @test
     */
    public function a_rule_with_an_invalid_condition_is_rejected(): void
    {
        $this->client->request('GET', '/admin/completeness-rules/new');

        $this->client->submitForm('Create', [
            'setono_sylius_completeness_completeness_rule[label]' => 'Broken rule',
            'setono_sylius_completeness_completeness_rule[type]' => 'has_name',
            'setono_sylius_completeness_completeness_rule[weightTier]' => 'medium',
            'setono_sylius_completeness_completeness_rule[position]' => '0',
            'setono_sylius_completeness_completeness_rule[condition]' => 'unknown_function(product)',
        ]);

        self::assertNull(
            $this->entityManager->getRepository(CompletenessRule::class)->findOneBy(['code' => 'broken_rule']),
        );
    }

    /**
     * @test
     */
    public function the_context_setting_grid_and_create_form_render(): void
    {
        $this->client->request('GET', '/admin/context-settings/');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/admin/context-settings/new');
        self::assertResponseIsSuccessful();
    }

    /**
     * @test
     */
    public function creating_a_context_setting_maps_the_checkbox_to_the_rollup_weight(): void
    {
        $channelCode = uniqid('COMPLETENESS_', true);

        $channel = new Channel();
        $channel->setCode($channelCode);
        $channel->setName('Completeness test channel');
        $channel->setTaxCalculationStrategy('order_items_based');
        $currency = new Currency();
        $currency->setCode('USD');
        $existingCurrency = $this->entityManager->getRepository(Currency::class)->findOneBy(['code' => 'USD']);
        if (null === $existingCurrency) {
            $this->entityManager->persist($currency);
            $this->entitiesToRemove[] = $currency;
        } else {
            $currency = $existingCurrency;
        }
        $channel->setBaseCurrency($currency);

        $locale = $this->entityManager->getRepository(Locale::class)->findOneBy(['code' => 'en_US']);
        if (null === $locale) {
            $locale = new Locale();
            $locale->setCode('en_US');
            $this->entityManager->persist($locale);
            $this->entitiesToRemove[] = $locale;
        }
        $channel->setDefaultLocale($locale);

        $this->entityManager->persist($channel);
        $this->entityManager->flush();
        $this->entitiesToRemove[] = $channel;

        $this->client->request('GET', '/admin/context-settings/new');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Create', [
            'setono_sylius_completeness_context_setting[channelCode]' => $channelCode,
            'setono_sylius_completeness_context_setting[localeCode]' => 'en_US',
            'setono_sylius_completeness_context_setting[threshold]' => '90',
            // untick "counts toward overall" => excluded from the rollup
            'setono_sylius_completeness_context_setting[countsTowardOverall]' => false,
        ]);

        self::assertResponseRedirects();

        $setting = $this->entityManager->getRepository(CompletenessContextSetting::class)->findOneBy([
            'channelCode' => $channelCode,
            'localeCode' => 'en_US',
        ]);

        self::assertInstanceOf(CompletenessContextSetting::class, $setting);
        self::assertSame(0.0, $setting->getRollupWeight());
        self::assertSame(90, $setting->getThreshold());
        $this->entitiesToRemove[] = $setting;
    }
}

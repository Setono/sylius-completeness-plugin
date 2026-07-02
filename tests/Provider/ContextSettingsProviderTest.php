<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Provider;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusCompletenessPlugin\Model\CompletenessContextSetting;
use Setono\SyliusCompletenessPlugin\Provider\ContextSettingsProvider;
use Setono\SyliusCompletenessPlugin\Repository\CompletenessContextSettingRepositoryInterface;

final class ContextSettingsProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_returns_defaults_when_no_setting_exists(): void
    {
        $repository = $this->prophesize(CompletenessContextSettingRepositoryInterface::class);
        $repository->findAll()->willReturn([]);

        $provider = new ContextSettingsProvider($repository->reveal());

        self::assertSame(1.0, $provider->getRollupWeight('WEB', 'en'));
        self::assertNull($provider->getThreshold('WEB', 'en'));
    }

    /**
     * @test
     */
    public function it_returns_the_configured_setting_and_memoizes_the_lookup(): void
    {
        $setting = new CompletenessContextSetting();
        $setting->setChannelCode('WEB');
        $setting->setLocaleCode('en');
        $setting->setThreshold(90);
        $setting->setRollupWeight(2.5);

        $repository = $this->prophesize(CompletenessContextSettingRepositoryInterface::class);
        $repository->findAll()->willReturn([$setting])->shouldBeCalledOnce();

        $provider = new ContextSettingsProvider($repository->reveal());

        self::assertSame(2.5, $provider->getRollupWeight('WEB', 'en'));
        self::assertSame(90, $provider->getThreshold('WEB', 'en'));
        self::assertSame(1.0, $provider->getRollupWeight('WEB', 'da'));
    }

    /**
     * @test
     */
    public function it_reloads_after_a_reset(): void
    {
        $repository = $this->prophesize(CompletenessContextSettingRepositoryInterface::class);
        $repository->findAll()->willReturn([])->shouldBeCalledTimes(2);

        $provider = new ContextSettingsProvider($repository->reveal());

        $provider->getRollupWeight('WEB', 'en');
        $provider->reset();
        $provider->getRollupWeight('WEB', 'en');
    }
}

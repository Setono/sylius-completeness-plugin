<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Resolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusCompletenessPlugin\Provider\CompletenessContextProviderInterface;
use Setono\SyliusCompletenessPlugin\Resolver\ThresholdResolver;

final class ThresholdResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_resolves_the_per_context_override(): void
    {
        $settings = $this->prophesize(CompletenessContextProviderInterface::class);
        $settings->getThreshold('WEB', 'en')->willReturn(90);

        self::assertSame(90, (new ThresholdResolver($settings->reveal(), 80))->resolve('WEB', 'en'));
    }

    /**
     * @test
     */
    public function it_falls_back_to_the_global_default(): void
    {
        $settings = $this->prophesize(CompletenessContextProviderInterface::class);
        $settings->getThreshold('WEB', 'en')->willReturn(null);

        $resolver = new ThresholdResolver($settings->reveal(), 80);

        self::assertSame(80, $resolver->resolve('WEB', 'en'));
        self::assertSame(80, $resolver->resolveDefault());
    }
}

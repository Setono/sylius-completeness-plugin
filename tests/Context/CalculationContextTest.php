<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Context;

use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Setono\SyliusCompletenessPlugin\Context\CalculationContext;
use Setono\SyliusCompletenessPlugin\Exception\NoActiveCalculationException;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Locale\Model\Locale;

final class CalculationContextTest extends TestCase
{
    /**
     * @test
     */
    public function it_throws_when_no_calculation_is_active(): void
    {
        $this->expectException(NoActiveCalculationException::class);

        (new CalculationContext())->get();
    }

    /**
     * @test
     */
    public function it_returns_the_published_context(): void
    {
        $calculationContext = new CalculationContext();
        $context = $this->createCheckContext();

        $calculationContext->set($context);

        self::assertSame($context, $calculationContext->get());
    }

    /**
     * @test
     */
    public function it_resets(): void
    {
        $calculationContext = new CalculationContext();
        $calculationContext->set($this->createCheckContext());

        $calculationContext->reset();

        $this->expectException(NoActiveCalculationException::class);
        $calculationContext->get();
    }

    private function createCheckContext(): CompletenessCheckContext
    {
        $channel = new Channel();
        $channel->setCode('WEB');

        $locale = new Locale();
        $locale->setCode('en');

        return new CompletenessCheckContext($channel, $locale);
    }
}

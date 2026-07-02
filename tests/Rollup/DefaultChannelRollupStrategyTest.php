<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Rollup;

use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\Rollup\DefaultChannelRollupStrategy;
use Setono\SyliusCompletenessPlugin\Rollup\RollupItem;
use Setono\SyliusCompletenessPlugin\Rollup\WeightedAverageRollupStrategy;

final class DefaultChannelRollupStrategyTest extends TestCase
{
    /**
     * @test
     */
    public function it_uses_only_the_default_channels_contexts(): void
    {
        $strategy = new DefaultChannelRollupStrategy(new WeightedAverageRollupStrategy(), 'WEB');

        self::assertSame(75, $strategy->rollup([
            new RollupItem('WEB', 'en', 100, 1.0),
            new RollupItem('WEB', 'da', 50, 1.0),
            new RollupItem('POS', 'en', 0, 1.0),
        ]));
    }

    /**
     * @test
     */
    public function it_falls_back_to_all_contexts_when_no_default_channel_is_configured(): void
    {
        $strategy = new DefaultChannelRollupStrategy(new WeightedAverageRollupStrategy(), null);

        self::assertSame(50, $strategy->rollup([
            new RollupItem('WEB', 'en', 100, 1.0),
            new RollupItem('POS', 'en', 0, 1.0),
        ]));
    }

    /**
     * @test
     */
    public function it_falls_back_when_the_product_has_no_contexts_in_the_default_channel(): void
    {
        $strategy = new DefaultChannelRollupStrategy(new WeightedAverageRollupStrategy(), 'OUTLET');

        self::assertSame(50, $strategy->rollup([
            new RollupItem('WEB', 'en', 100, 1.0),
            new RollupItem('POS', 'en', 0, 1.0),
        ]));
    }
}

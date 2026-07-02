<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Rollup;

use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\Rollup\RollupItem;
use Setono\SyliusCompletenessPlugin\Rollup\WeightedAverageRollupStrategy;

final class WeightedAverageRollupStrategyTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_a_flat_average_when_all_weights_are_equal(): void
    {
        $strategy = new WeightedAverageRollupStrategy();

        self::assertSame(50, $strategy->rollup([
            new RollupItem('WEB', 'en', 100, 1.0),
            new RollupItem('WEB', 'da', 0, 1.0),
        ]));
    }

    /**
     * @test
     */
    public function it_weights_contexts(): void
    {
        $strategy = new WeightedAverageRollupStrategy();

        // (100 * 3 + 0 * 1) / 4 = 75
        self::assertSame(75, $strategy->rollup([
            new RollupItem('WEB', 'en', 100, 3.0),
            new RollupItem('WEB', 'da', 0, 1.0),
        ]));
    }
}

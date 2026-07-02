<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Rollup;

use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\Rollup\MinimumRollupStrategy;
use Setono\SyliusCompletenessPlugin\Rollup\RollupItem;

final class MinimumRollupStrategyTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_lowest_ratio(): void
    {
        self::assertSame(20, (new MinimumRollupStrategy())->rollup([
            new RollupItem('WEB', 'en', 100, 1.0),
            new RollupItem('WEB', 'da', 20, 1.0),
            new RollupItem('POS', 'en', 60, 1.0),
        ]));
    }
}

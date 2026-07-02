<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Rollup;

/**
 * Collapses the per context ratios into the single global product ratio. N/A (null ratio) and
 * excluded (rollup weight 0) contexts are dropped BEFORE a strategy is invoked, for every
 * strategy, so implementations always receive a non empty list of measured, counting contexts
 */
interface RollupStrategyInterface
{
    public static function getName(): string;

    /**
     * @param non-empty-list<RollupItem> $items
     */
    public function rollup(array $items): int;
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Rollup;

/**
 * The strictest strategy: the product is only as complete as its least complete context
 */
final class MinimumRollupStrategy implements RollupStrategyInterface
{
    public static function getName(): string
    {
        return 'minimum';
    }

    public function rollup(array $items): int
    {
        return min(array_map(static fn (RollupItem $item): int => $item->ratio, $items));
    }
}

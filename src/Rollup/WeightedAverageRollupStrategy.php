<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Rollup;

/**
 * The default strategy: a weighted average using each context's rollup weight. Identical to a
 * flat average as long as no per context weights are configured
 */
final class WeightedAverageRollupStrategy implements RollupStrategyInterface
{
    public static function getName(): string
    {
        return 'weighted_average';
    }

    public function rollup(array $items): int
    {
        $weightedSum = 0.0;
        $weightTotal = 0.0;

        foreach ($items as $item) {
            $weightedSum += $item->ratio * $item->weight;
            $weightTotal += $item->weight;
        }

        return (int) round($weightedSum / $weightTotal);
    }
}

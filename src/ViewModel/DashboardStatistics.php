<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\ViewModel;

/**
 * Catalog-wide completeness figures shown on the dashboard.
 */
final class DashboardStatistics
{
    /**
     * @param int $averageRatio the average completeness of the scored products (0 when none are scored)
     * @param int $readyThreshold the ratio at or above which a product counts as "ready"
     * @param list<array{from: int, to: int, count: int}> $distribution scored-product counts per 20-point band
     */
    public function __construct(
        public readonly int $totalProducts,
        public readonly int $scoredProducts,
        public readonly int $averageRatio,
        public readonly int $readyProducts,
        public readonly int $staleProducts,
        public readonly int $readyThreshold,
        public readonly array $distribution,
    ) {
    }

    public function scoredPercentage(): int
    {
        if (0 === $this->totalProducts) {
            return 0;
        }

        return (int) round(100 * $this->scoredProducts / $this->totalProducts);
    }

    public function readyPercentage(): int
    {
        if (0 === $this->scoredProducts) {
            return 0;
        }

        return (int) round(100 * $this->readyProducts / $this->scoredProducts);
    }

    public function largestBandCount(): int
    {
        $max = 0;
        foreach ($this->distribution as $band) {
            $max = max($max, $band['count']);
        }

        return $max;
    }
}

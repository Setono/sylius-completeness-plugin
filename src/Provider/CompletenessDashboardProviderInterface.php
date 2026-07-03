<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Provider;

use Setono\SyliusCompletenessPlugin\ViewModel\DashboardStatistics;
use Sylius\Component\Core\Model\ProductInterface;

interface CompletenessDashboardProviderInterface
{
    public function getStatistics(): DashboardStatistics;

    /**
     * The scored products with the lowest completeness, ascending - the ones most in need of work.
     *
     * @return list<ProductInterface>
     */
    public function getLowestScoringProducts(int $limit): array;
}

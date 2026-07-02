<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Repository;

use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<ProductCompletenessInterface>
 */
interface ProductCompletenessRepositoryInterface extends RepositoryInterface
{
    /**
     * Returns the persisted per-context ratios for the given products without hydrating entities,
     * indexed by product id. Intended for rollup-only refreshes
     *
     * @param list<int> $productIds
     *
     * @return array<int, list<array{channelCode: string, localeCode: string, ratio: ?int}>>
     */
    public function findRatiosGroupedByProduct(array $productIds): array;
}

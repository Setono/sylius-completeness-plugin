<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Updater;

use Setono\SyliusCompletenessPlugin\Calculator\Result\ProductCompletenessResult;
use Sylius\Component\Core\Model\ProductInterface;

/**
 * Calculates AND persists a product's completeness: upserts the per context rows, prunes rows
 * for contexts that no longer exist, writes the global rollup + rubric version onto the product,
 * flushes and dispatches the ProductCompletenessCalculated event
 */
interface ProductCompletenessUpdaterInterface
{
    /**
     * @param bool $bulk whether this update is part of a catalog wide run (propagated to the event)
     *
     * @throws \InvalidArgumentException when the product does not implement ProductCompletenessAwareInterface
     */
    public function update(ProductInterface $product, bool $bulk = false): ProductCompletenessResult;
}

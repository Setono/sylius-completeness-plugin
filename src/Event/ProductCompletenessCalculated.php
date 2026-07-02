<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Event;

use Setono\SyliusCompletenessPlugin\Calculator\Result\ProductCompletenessResult;
use Sylius\Component\Core\Model\ProductInterface;

/**
 * Dispatched after a product's completeness has been calculated AND persisted. The bulk flag is
 * true during catalog wide runs (rule/context setting changes, console --all, bulk grid action),
 * so listeners can cheaply opt out of per product work during mass recalculations. The event
 * itself is never dropped
 */
final class ProductCompletenessCalculated
{
    public function __construct(
        public readonly ProductInterface $product,
        public readonly ProductCompletenessResult $result,
        public readonly bool $bulk,
    ) {
    }
}

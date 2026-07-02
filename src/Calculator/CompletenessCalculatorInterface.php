<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Calculator;

use Setono\SyliusCompletenessPlugin\Calculator\Result\ProductCompletenessResult;
use Sylius\Component\Core\Model\ProductInterface;

/**
 * The public, stable calculator contract. Calculation is PURE: it writes nothing and returns the
 * full breakdown, so this is also the dry-run/preview path. Use the updater to persist a result
 */
interface CompletenessCalculatorInterface
{
    public function calculate(ProductInterface $product): ProductCompletenessResult;
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Calculator;

use Setono\SyliusCompletenessPlugin\Calculator\Result\ContextResult;
use Setono\SyliusCompletenessPlugin\Calculator\Result\ProductCompletenessResult;
use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Sylius\Component\Core\Model\ProductInterface;

/**
 * The public, stable calculator contract. Calculation is PURE: it writes nothing and returns the
 * full breakdown, so this is also the dry-run/preview path. Use the updater to persist a result
 */
interface CompletenessCalculatorInterface
{
    public function calculate(ProductInterface $product): ProductCompletenessResult;

    /**
     * Evaluates the rubric for a single, arbitrary context - even one the product is not assigned
     * to. Used by the preview screen to test any (channel, locale) combination
     */
    public function calculateContext(ProductInterface $product, CompletenessCheckContext $context): ContextResult;
}

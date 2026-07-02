<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Calculator;

use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Sylius\Component\Core\Model\ProductInterface;

interface ContextInitializerInterface
{
    /**
     * Prepares a product for evaluation in a context (spec §4 step 0): sets the product's current
     * AND fallback locale to the context locale - fallback EQUAL to current, so a genuinely missing
     * translation reads as empty instead of inheriting the default locale text - and publishes the
     * context to the calculation scoped holder that the locale/channel implicit expression
     * functions read
     */
    public function initialize(ProductInterface $product, CompletenessCheckContext $context): void;

    /**
     * Clears the calculation scoped context holder after a calculation run
     */
    public function terminate(): void;
}

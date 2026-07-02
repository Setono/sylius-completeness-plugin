<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Calculator;

use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Setono\SyliusCompletenessPlugin\Context\CalculationContextInterface;
use Sylius\Component\Core\Model\ProductInterface;

final class ContextInitializer implements ContextInitializerInterface
{
    public function __construct(private readonly CalculationContextInterface $calculationContext)
    {
    }

    public function initialize(ProductInterface $product, CompletenessCheckContext $context): void
    {
        $localeCode = $context->getLocaleCode();

        $product->setCurrentLocale($localeCode);
        $product->setFallbackLocale($localeCode);

        $this->calculationContext->set($context);
    }

    public function terminate(): void
    {
        $this->calculationContext->set(null);
    }
}

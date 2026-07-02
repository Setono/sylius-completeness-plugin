<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Checker;

use Setono\SyliusCompletenessPlugin\Util\Text;
use Sylius\Component\Core\Model\ProductInterface;

/**
 * Relies on the calculator having set the product's current AND fallback locale to the context
 * locale, so a missing translation reads as empty instead of inheriting the default locale text
 */
final class HasDescriptionChecker extends BinaryChecker
{
    public static function getType(): string
    {
        return 'has_description';
    }

    public static function getGroup(): string
    {
        return 'content';
    }

    protected function isSatisfied(ProductInterface $product, CompletenessCheckContext $context, array $configuration): bool
    {
        return !Text::isBlank($product->getDescription());
    }
}

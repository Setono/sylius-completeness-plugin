<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Checker;

use Setono\SyliusCompletenessPlugin\Exception\InvalidCheckerConfigurationException;
use Setono\SyliusCompletenessPlugin\Util\Attributes;
use Sylius\Component\Core\Model\ProductInterface;

/**
 * Satisfied when the product has a non empty value for the configured attribute. The locale is passed
 * explicitly, so localizable attributes are read for the context locale without fallback while
 * non localizable attributes (stored with a null locale) match any locale
 */
final class HasAttributeChecker extends BinaryChecker
{
    public static function getType(): string
    {
        return 'has_attribute';
    }

    public static function getGroup(): string
    {
        return 'content';
    }

    protected function isSatisfied(ProductInterface $product, CompletenessCheckContext $context, array $configuration): bool
    {
        $attributeCode = $configuration['attributeCode'] ?? null;
        if (!is_string($attributeCode) || '' === $attributeCode) {
            throw new InvalidCheckerConfigurationException('The has_attribute checker expects an "attributeCode" configuration value');
        }

        $attributeValue = $product->getAttributeByCodeAndLocale($attributeCode, $context->getLocaleCode());

        return null !== $attributeValue && !Attributes::isEmpty($attributeValue->getValue());
    }
}

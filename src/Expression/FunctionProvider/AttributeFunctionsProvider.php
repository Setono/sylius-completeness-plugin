<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression\FunctionProvider;

use Setono\SyliusCompletenessPlugin\Context\CalculationContextInterface;
use Setono\SyliusCompletenessPlugin\Util\Attributes;
use Setono\SyliusCompletenessPlugin\Util\Text;

final class AttributeFunctionsProvider extends FunctionProvider
{
    public function __construct(private readonly CalculationContextInterface $calculationContext)
    {
    }

    public function getFunctions(): array
    {
        return [
            $this->createFunction(
                'has_attribute',
                'has_attribute(product, code[, locale]): bool',
                'True when the product has a non-empty value for the attribute code.',
                function (array $variables, mixed $product, mixed $code, mixed $locale = null): bool {
                    $product = $this->assertProduct($product, 'has_attribute');
                    $attributeValue = $product->getAttributeByCodeAndLocale(Text::coerce($code), $this->resolveLocale($locale));

                    return null !== $attributeValue && !Attributes::isEmpty($attributeValue->getValue());
                },
            ),
            $this->createFunction(
                'attribute_value',
                'attribute_value(product, code[, locale]): mixed',
                'The product\'s value for the attribute code; for select attributes this is the stored option code, not the label.',
                function (array $variables, mixed $product, mixed $code, mixed $locale = null): mixed {
                    $product = $this->assertProduct($product, 'attribute_value');
                    $value = $product->getAttributeByCodeAndLocale(Text::coerce($code), $this->resolveLocale($locale))?->getValue();

                    if (null === $value) {
                        return '';
                    }

                    if (is_array($value)) {
                        return array_values($value)[0] ?? '';
                    }

                    return $value;
                },
            ),
            $this->createFunction(
                'attribute_values',
                'attribute_values(product, code[, locale]): list',
                'The product\'s values for a multi-value attribute code, as a list.',
                function (array $variables, mixed $product, mixed $code, mixed $locale = null): array {
                    $product = $this->assertProduct($product, 'attribute_values');
                    $value = $product->getAttributeByCodeAndLocale(Text::coerce($code), $this->resolveLocale($locale))?->getValue();

                    if (null === $value) {
                        return [];
                    }

                    if (is_array($value)) {
                        return array_values($value);
                    }

                    return [$value];
                },
            ),
            $this->createFunction(
                'attribute_count',
                'attribute_count(product): int',
                'The number of attribute values set on the product.',
                function (array $variables, mixed $product): int {
                    $product = $this->assertProduct($product, 'attribute_count');
                    $localeCode = $this->calculationContext->get()->getLocaleCode();

                    $count = 0;
                    foreach ($product->getAttributes() as $attributeValue) {
                        $valueLocale = $attributeValue->getLocaleCode();
                        if (($valueLocale === $localeCode || null === $valueLocale) && !Attributes::isEmpty($attributeValue->getValue())) {
                            ++$count;
                        }
                    }

                    return $count;
                },
            ),
        ];
    }

    private function resolveLocale(mixed $locale): string
    {
        return $this->toNullableString($locale) ?? $this->calculationContext->get()->getLocaleCode();
    }
}

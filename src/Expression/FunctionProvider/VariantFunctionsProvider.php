<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression\FunctionProvider;

use Setono\SyliusCompletenessPlugin\Util\Text;
use Sylius\Component\Product\Model\ProductVariantInterface;

final class VariantFunctionsProvider extends FunctionProvider
{
    public function getFunctions(): array
    {
        return [
            $this->createFunction(
                'variant_count',
                'variant_count(product): int',
                'The number of variants of the product.',
                fn (array $variables, mixed $product): int => $this->assertProduct($product, 'variant_count')->getVariants()->count(),
            ),
            $this->createFunction(
                'enabled_variant_count',
                'enabled_variant_count(product): int',
                'The number of enabled variants of the product.',
                function (array $variables, mixed $product): int {
                    $count = 0;
                    foreach ($this->assertProduct($product, 'enabled_variant_count')->getVariants() as $variant) {
                        if ($variant instanceof ProductVariantInterface && $variant->isEnabled()) {
                            ++$count;
                        }
                    }

                    return $count;
                },
            ),
            $this->createFunction(
                'has_option',
                'has_option(product, optionCode): bool',
                'True when the product has the given product option.',
                function (array $variables, mixed $product, mixed $optionCode): bool {
                    $optionCode = Text::coerce($optionCode);
                    foreach ($this->assertProduct($product, 'has_option')->getOptions() as $option) {
                        if ($option->getCode() === $optionCode) {
                            return true;
                        }
                    }

                    return false;
                },
            ),
            $this->createFunction(
                'option_count',
                'option_count(product): int',
                'The number of product options on the product.',
                fn (array $variables, mixed $product): int => $this->assertProduct($product, 'option_count')->getOptions()->count(),
            ),
            $this->createFunction(
                'association_count',
                'association_count(product, typeCode): int',
                'The number of associated products for the given association type code.',
                function (array $variables, mixed $product, mixed $typeCode): int {
                    $typeCode = Text::coerce($typeCode);
                    foreach ($this->assertProduct($product, 'association_count')->getAssociations() as $association) {
                        if ($association->getType()?->getCode() === $typeCode) {
                            return $association->getAssociatedProducts()->count();
                        }
                    }

                    return 0;
                },
            ),
        ];
    }
}

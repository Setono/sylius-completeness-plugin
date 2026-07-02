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
                fn (array $variables, mixed $product): int => $this->assertProduct($product, 'variant_count')->getVariants()->count(),
            ),
            $this->createFunction(
                'enabled_variant_count',
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
                fn (array $variables, mixed $product): int => $this->assertProduct($product, 'option_count')->getOptions()->count(),
            ),
            $this->createFunction(
                'association_count',
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

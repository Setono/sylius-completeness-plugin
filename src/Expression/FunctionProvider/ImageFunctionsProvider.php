<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression\FunctionProvider;

use Setono\SyliusCompletenessPlugin\Util\Text;

final class ImageFunctionsProvider extends FunctionProvider
{
    public function getFunctions(): array
    {
        return [
            $this->createFunction(
                'image_count',
                fn (array $variables, mixed $product): int => $this->assertProduct($product, 'image_count')->getImages()->count(),
            ),
            $this->createFunction(
                'has_image',
                fn (array $variables, mixed $product): bool => !$this->assertProduct($product, 'has_image')->getImages()->isEmpty(),
            ),
            $this->createFunction(
                'image_count_of_type',
                fn (array $variables, mixed $product, mixed $type): int => $this
                    ->assertProduct($product, 'image_count_of_type')
                    ->getImagesByType(Text::coerce($type))
                    ->count(),
            ),
            $this->createFunction(
                'has_image_type',
                fn (array $variables, mixed $product, mixed $type): bool => !$this
                    ->assertProduct($product, 'has_image_type')
                    ->getImagesByType(Text::coerce($type))
                    ->isEmpty(),
            ),
        ];
    }
}

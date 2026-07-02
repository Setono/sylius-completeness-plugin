<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Checker;

use Sylius\Component\Core\Model\ProductInterface;

final class HasImageChecker extends BinaryChecker
{
    public static function getType(): string
    {
        return 'has_image';
    }

    protected function isSatisfied(ProductInterface $product, CompletenessCheckContext $context, array $configuration): bool
    {
        return !$product->getImages()->isEmpty();
    }
}

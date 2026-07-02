<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Doctrine\Resolver;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class ProductVariantResolver implements AffectedProductsResolverInterface
{
    public function getSupportedClasses(): array
    {
        return [ProductVariantInterface::class];
    }

    public function getProducts(object $entity): iterable
    {
        if (!$entity instanceof ProductVariantInterface) {
            return;
        }

        $product = $entity->getProduct();
        if ($product instanceof ProductInterface) {
            yield $product;
        }
    }
}

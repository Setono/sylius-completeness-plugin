<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Doctrine\Resolver;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;

final class ProductAttributeValueResolver implements AffectedProductsResolverInterface
{
    public function getSupportedClasses(): array
    {
        return [ProductAttributeValueInterface::class];
    }

    public function getProducts(object $entity): iterable
    {
        if (!$entity instanceof ProductAttributeValueInterface) {
            return;
        }

        $product = $entity->getProduct();
        if ($product instanceof ProductInterface) {
            yield $product;
        }
    }
}

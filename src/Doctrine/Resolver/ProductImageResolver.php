<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Doctrine\Resolver;

use Sylius\Component\Core\Model\ProductImageInterface;
use Sylius\Component\Core\Model\ProductInterface;

final class ProductImageResolver implements AffectedProductsResolverInterface
{
    public function getSupportedClasses(): array
    {
        return [ProductImageInterface::class];
    }

    public function getProducts(object $entity): iterable
    {
        if (!$entity instanceof ProductImageInterface) {
            return;
        }

        $product = $entity->getOwner();
        if ($product instanceof ProductInterface) {
            yield $product;
        }
    }
}

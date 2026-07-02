<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Doctrine\Resolver;

use Sylius\Component\Core\Model\ProductInterface;

final class ProductResolver implements AffectedProductsResolverInterface
{
    public function getSupportedClasses(): array
    {
        return [ProductInterface::class];
    }

    public function getProducts(object $entity): iterable
    {
        if ($entity instanceof ProductInterface) {
            yield $entity;
        }
    }
}

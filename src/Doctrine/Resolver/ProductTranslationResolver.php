<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Doctrine\Resolver;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductTranslationInterface;

final class ProductTranslationResolver implements AffectedProductsResolverInterface
{
    public function getSupportedClasses(): array
    {
        return [ProductTranslationInterface::class];
    }

    public function getProducts(object $entity): iterable
    {
        if (!$entity instanceof ProductTranslationInterface) {
            return;
        }

        $product = $entity->getTranslatable();
        if ($product instanceof ProductInterface) {
            yield $product;
        }
    }
}

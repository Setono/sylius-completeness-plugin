<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Doctrine\Resolver;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;

final class ProductTaxonResolver implements AffectedProductsResolverInterface
{
    public function getSupportedClasses(): array
    {
        return [ProductTaxonInterface::class];
    }

    public function getProducts(object $entity): iterable
    {
        if (!$entity instanceof ProductTaxonInterface) {
            return;
        }

        $product = $entity->getProduct();
        if ($product instanceof ProductInterface) {
            yield $product;
        }
    }
}

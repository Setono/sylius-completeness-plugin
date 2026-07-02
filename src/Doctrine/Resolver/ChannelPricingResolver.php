<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Doctrine\Resolver;

use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductInterface;

final class ChannelPricingResolver implements AffectedProductsResolverInterface
{
    public function getSupportedClasses(): array
    {
        return [ChannelPricingInterface::class];
    }

    public function getProducts(object $entity): iterable
    {
        if (!$entity instanceof ChannelPricingInterface) {
            return;
        }

        $product = $entity->getProductVariant()?->getProduct();
        if ($product instanceof ProductInterface) {
            yield $product;
        }
    }
}

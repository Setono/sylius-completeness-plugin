<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Checker;

use Setono\SyliusCompletenessPlugin\Util\Pricing;
use Sylius\Component\Core\Model\ProductInterface;

/**
 * Satisfied when at least one enabled variant is priced in the context channel
 */
final class HasPriceChecker extends BinaryChecker
{
    public static function getType(): string
    {
        return 'has_price';
    }

    protected function isSatisfied(ProductInterface $product, CompletenessCheckContext $context, array $configuration): bool
    {
        return Pricing::hasPriceInChannel($product, $context->getChannelCode());
    }
}

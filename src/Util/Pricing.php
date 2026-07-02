<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Util;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * Pricing helpers shared by the has_price checker and the channel implicit expression functions.
 * The channel pricings collection is indexed by channel code in the Sylius mapping, so lookups by
 * code are cheap and do not require a channel instance
 */
final class Pricing
{
    private function __construct()
    {
    }

    /**
     * Returns true if at least one enabled variant has a price in the given channel
     */
    public static function hasPriceInChannel(ProductInterface $product, string $channelCode): bool
    {
        return null !== self::lowestPriceInChannel($product, $channelCode);
    }

    /**
     * Returns the lowest price (in minor units) among the enabled variants in the given channel
     * or null if no enabled variant is priced in the channel
     */
    public static function lowestPriceInChannel(ProductInterface $product, string $channelCode): ?int
    {
        $lowest = null;

        foreach ($product->getVariants() as $variant) {
            if (!$variant instanceof ProductVariantInterface || !$variant->isEnabled()) {
                continue;
            }

            $price = $variant->getChannelPricings()->get($channelCode)?->getPrice();
            if (null === $price) {
                continue;
            }

            if (null === $lowest || $price < $lowest) {
                $lowest = $price;
            }
        }

        return $lowest;
    }
}

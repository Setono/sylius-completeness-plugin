<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Expression\FunctionProvider;

use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelPricing;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductVariant;

final class ChannelFunctionsProviderTest extends FunctionProviderTestCase
{
    /**
     * @test
     */
    public function it_checks_whether_the_product_is_enabled(): void
    {
        $product = $this->createProduct();
        $product->setEnabled(false);

        self::assertFalse($this->evaluate('is_enabled(product)', ['product' => $product]));
    }

    /**
     * @test
     */
    public function it_checks_channel_membership_with_implicit_and_explicit_channel(): void
    {
        $channel = new Channel();
        $channel->setCode('WEB');

        $product = $this->createProduct();
        $product->addChannel($channel);

        $this->publishContext('WEB', 'en');
        self::assertTrue($this->evaluate('is_in_channel(product)', ['product' => $product]));
        self::assertFalse($this->evaluate('is_in_channel(product, "POS")', ['product' => $product]));
        self::assertSame(1, $this->evaluate('channel_count(product)', ['product' => $product]));
    }

    /**
     * @test
     */
    public function it_checks_prices_with_implicit_and_explicit_channel(): void
    {
        $product = $this->createPricedProduct('WEB', 1000);

        $this->publishContext('WEB', 'en');
        self::assertTrue($this->evaluate('has_price(product)', ['product' => $product]));
        self::assertFalse($this->evaluate('has_price(product, "POS")', ['product' => $product]));
    }

    /**
     * @test
     */
    public function it_returns_the_lowest_enabled_variant_price(): void
    {
        $product = $this->createPricedProduct('WEB', 1000);

        $cheaperVariant = new ProductVariant();
        $cheaperVariant->setEnabled(true);
        $cheaperPricing = new ChannelPricing();
        $cheaperPricing->setChannelCode('WEB');
        $cheaperPricing->setPrice(500);
        $cheaperVariant->addChannelPricing($cheaperPricing);
        $product->addVariant($cheaperVariant);

        $this->publishContext('WEB', 'en');
        self::assertSame(500, $this->evaluate('price(product)', ['product' => $product]));
        self::assertSame(0, $this->evaluate('price(product, "POS")', ['product' => $product]));
    }

    private function createPricedProduct(string $channelCode, int $price): Product
    {
        $channelPricing = new ChannelPricing();
        $channelPricing->setChannelCode($channelCode);
        $channelPricing->setPrice($price);

        $variant = new ProductVariant();
        $variant->setEnabled(true);
        $variant->addChannelPricing($channelPricing);

        $product = $this->createProduct();
        $product->addVariant($variant);

        return $product;
    }
}

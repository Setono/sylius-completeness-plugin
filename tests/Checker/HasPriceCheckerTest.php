<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Checker;

use Setono\SyliusCompletenessPlugin\Checker\HasPriceChecker;
use Sylius\Component\Core\Model\ChannelPricing;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductVariant;

final class HasPriceCheckerTest extends CheckerTestCase
{
    /**
     * @test
     */
    public function it_scores_one_when_an_enabled_variant_is_priced_in_the_channel(): void
    {
        $product = $this->createProductWithVariant(enabled: true, channelCode: 'WEB', price: 1000);

        self::assertSame(1.0, (new HasPriceChecker())->score($product, $this->createContext('WEB'), []));
    }

    /**
     * @test
     */
    public function it_scores_zero_when_the_priced_variant_is_disabled(): void
    {
        $product = $this->createProductWithVariant(enabled: false, channelCode: 'WEB', price: 1000);

        self::assertSame(0.0, (new HasPriceChecker())->score($product, $this->createContext('WEB'), []));
    }

    /**
     * @test
     */
    public function it_scores_zero_when_the_price_is_null(): void
    {
        $product = $this->createProductWithVariant(enabled: true, channelCode: 'WEB', price: null);

        self::assertSame(0.0, (new HasPriceChecker())->score($product, $this->createContext('WEB'), []));
    }

    /**
     * @test
     */
    public function it_scores_zero_when_the_variant_is_priced_in_another_channel(): void
    {
        $product = $this->createProductWithVariant(enabled: true, channelCode: 'POS', price: 1000);

        self::assertSame(0.0, (new HasPriceChecker())->score($product, $this->createContext('WEB'), []));
    }

    /**
     * @test
     */
    public function it_scores_zero_when_the_product_has_no_variants(): void
    {
        self::assertSame(0.0, (new HasPriceChecker())->score($this->createProduct(), $this->createContext('WEB'), []));
    }

    private function createProductWithVariant(bool $enabled, string $channelCode, ?int $price): Product
    {
        $channelPricing = new ChannelPricing();
        $channelPricing->setChannelCode($channelCode);
        $channelPricing->setPrice($price);

        $variant = new ProductVariant();
        $variant->setEnabled($enabled);
        $variant->addChannelPricing($channelPricing);

        $product = $this->createProduct();
        $product->addVariant($variant);

        return $product;
    }
}

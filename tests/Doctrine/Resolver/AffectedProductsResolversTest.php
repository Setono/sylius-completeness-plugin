<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Doctrine\Resolver;

use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\AffectedProductsResolverInterface;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\ChannelPricingResolver;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\ProductAttributeValueResolver;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\ProductImageResolver;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\ProductResolver;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\ProductTaxonResolver;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\ProductTranslationResolver;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\ProductVariantResolver;
use Sylius\Component\Core\Model\ChannelPricing;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductImage;
use Sylius\Component\Core\Model\ProductTaxon;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Product\Model\ProductAttributeValue;
use Sylius\Component\Product\Model\ProductTranslation;

final class AffectedProductsResolversTest extends TestCase
{
    /**
     * @test
     */
    public function the_product_resolver_yields_the_product_itself(): void
    {
        $product = new Product();

        self::assertResolvesTo($product, new ProductResolver(), $product);
    }

    /**
     * @test
     */
    public function the_translation_resolver_yields_the_translatable(): void
    {
        $product = new Product();

        $translation = new ProductTranslation();
        $translation->setTranslatable($product);

        self::assertResolvesTo($product, new ProductTranslationResolver(), $translation);
    }

    /**
     * @test
     */
    public function the_image_resolver_yields_the_owner(): void
    {
        $product = new Product();

        $image = new ProductImage();
        $image->setOwner($product);

        self::assertResolvesTo($product, new ProductImageResolver(), $image);
    }

    /**
     * @test
     */
    public function the_variant_resolver_yields_the_product(): void
    {
        $product = new Product();

        $variant = new ProductVariant();
        $variant->setProduct($product);

        self::assertResolvesTo($product, new ProductVariantResolver(), $variant);
    }

    /**
     * @test
     */
    public function the_channel_pricing_resolver_yields_the_variants_product(): void
    {
        $product = new Product();

        $variant = new ProductVariant();
        $variant->setProduct($product);

        $channelPricing = new ChannelPricing();
        $channelPricing->setProductVariant($variant);

        self::assertResolvesTo($product, new ChannelPricingResolver(), $channelPricing);
    }

    /**
     * @test
     */
    public function the_attribute_value_resolver_yields_the_product(): void
    {
        $product = new Product();

        $attributeValue = new ProductAttributeValue();
        $attributeValue->setProduct($product);

        self::assertResolvesTo($product, new ProductAttributeValueResolver(), $attributeValue);
    }

    /**
     * @test
     */
    public function the_product_taxon_resolver_yields_the_product(): void
    {
        $product = new Product();

        $productTaxon = new ProductTaxon();
        $productTaxon->setProduct($product);

        self::assertResolvesTo($product, new ProductTaxonResolver(), $productTaxon);
    }

    /**
     * @test
     */
    public function resolvers_yield_nothing_for_detached_children(): void
    {
        self::assertSame([], self::toList((new ProductVariantResolver())->getProducts(new ProductVariant())));
        self::assertSame([], self::toList((new ChannelPricingResolver())->getProducts(new ChannelPricing())));
    }

    private static function assertResolvesTo(Product $expected, AffectedProductsResolverInterface $resolver, object $entity): void
    {
        self::assertNotSame([], $resolver->getSupportedClasses());
        self::assertSame([$expected], self::toList($resolver->getProducts($entity)));
    }

    /**
     * @param iterable<object> $products
     *
     * @return list<object>
     */
    private static function toList(iterable $products): array
    {
        $list = [];
        foreach ($products as $product) {
            $list[] = $product;
        }

        return $list;
    }
}

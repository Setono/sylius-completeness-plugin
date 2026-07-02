<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Expression\FunctionProvider;

use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Product\Model\ProductAssociation;
use Sylius\Component\Product\Model\ProductAssociationType;
use Sylius\Component\Product\Model\ProductOption;

final class VariantFunctionsProviderTest extends FunctionProviderTestCase
{
    /**
     * @test
     */
    public function it_counts_variants(): void
    {
        $product = $this->createProduct();
        $product->addVariant($this->createVariant(enabled: true));
        $product->addVariant($this->createVariant(enabled: false));

        self::assertSame(2, $this->evaluate('variant_count(product)', ['product' => $product]));
        self::assertSame(1, $this->evaluate('enabled_variant_count(product)', ['product' => $product]));
    }

    /**
     * @test
     */
    public function it_checks_and_counts_options(): void
    {
        $option = new ProductOption();
        $option->setCode('size');

        $product = $this->createProduct();
        $product->addOption($option);

        self::assertTrue($this->evaluate('has_option(product, "size")', ['product' => $product]));
        self::assertFalse($this->evaluate('has_option(product, "color")', ['product' => $product]));
        self::assertSame(1, $this->evaluate('option_count(product)', ['product' => $product]));
    }

    /**
     * @test
     */
    public function it_counts_associated_products(): void
    {
        $type = new ProductAssociationType();
        $type->setCode('upsell');

        $association = new ProductAssociation();
        $association->setType($type);
        $association->addAssociatedProduct(new Product());
        $association->addAssociatedProduct(new Product());

        $product = $this->createProduct();
        $product->addAssociation($association);

        self::assertSame(2, $this->evaluate('association_count(product, "upsell")', ['product' => $product]));
        self::assertSame(0, $this->evaluate('association_count(product, "cross_sell")', ['product' => $product]));
    }

    private function createVariant(bool $enabled): ProductVariant
    {
        $variant = new ProductVariant();
        $variant->setEnabled($enabled);

        return $variant;
    }
}

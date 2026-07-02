<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Expression\FunctionProvider;

use Sylius\Component\Core\Model\ProductTaxon;
use Sylius\Component\Core\Model\Taxon;

final class TaxonFunctionsProviderTest extends FunctionProviderTestCase
{
    /**
     * @test
     */
    public function it_checks_the_main_taxon(): void
    {
        $product = $this->createProduct();
        self::assertFalse($this->evaluate('has_main_taxon(product)', ['product' => $product]));

        $product->setMainTaxon($this->createTaxon('shirts'));
        self::assertTrue($this->evaluate('has_main_taxon(product)', ['product' => $product]));
    }

    /**
     * @test
     */
    public function it_matches_the_main_taxon_and_product_taxons(): void
    {
        $product = $this->createProduct();
        $product->setMainTaxon($this->createTaxon('shirts'));

        $productTaxon = new ProductTaxon();
        $productTaxon->setTaxon($this->createTaxon('sale'));
        $productTaxon->setProduct($product);
        $product->addProductTaxon($productTaxon);

        self::assertTrue($this->evaluate('in_taxon(product, "shirts")', ['product' => $product]));
        self::assertTrue($this->evaluate('in_taxon(product, "sale")', ['product' => $product]));
        self::assertFalse($this->evaluate('in_taxon(product, "pants")', ['product' => $product]));
    }

    /**
     * @test
     */
    public function it_lists_and_counts_unique_taxon_codes(): void
    {
        $product = $this->createProduct();
        $mainTaxon = $this->createTaxon('shirts');
        $product->setMainTaxon($mainTaxon);

        $productTaxon = new ProductTaxon();
        $productTaxon->setTaxon($mainTaxon);
        $productTaxon->setProduct($product);
        $product->addProductTaxon($productTaxon);

        $saleTaxon = new ProductTaxon();
        $saleTaxon->setTaxon($this->createTaxon('sale'));
        $saleTaxon->setProduct($product);
        $product->addProductTaxon($saleTaxon);

        self::assertSame(['shirts', 'sale'], $this->evaluate('taxon_codes(product)', ['product' => $product]));
        self::assertSame(2, $this->evaluate('taxon_count(product)', ['product' => $product]));
    }

    private function createTaxon(string $code): Taxon
    {
        $taxon = new Taxon();
        $taxon->setCode($code);

        return $taxon;
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\Model\ProductCompleteness;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessAwareInterface;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessAwareTrait;
use Sylius\Component\Core\Model\Product as BaseProduct;

final class ProductCompletenessAwareTraitTest extends TestCase
{
    /**
     * @test
     */
    public function it_defaults_to_a_null_ratio_and_an_empty_collection(): void
    {
        $product = $this->createProduct();

        self::assertNull($product->getCompletenessRatio());
        self::assertNull($product->getCompletenessRubricVersion());
        self::assertTrue($product->getCompletenesses()->isEmpty());
    }

    /**
     * @test
     */
    public function it_adds_a_completeness_and_sets_the_owning_side(): void
    {
        $product = $this->createProduct();
        $completeness = new ProductCompleteness();

        $product->addCompleteness($completeness);

        self::assertTrue($product->hasCompleteness($completeness));
        self::assertSame($product, $completeness->getProduct());
        self::assertCount(1, $product->getCompletenesses());
    }

    /**
     * @test
     */
    public function it_does_not_add_the_same_completeness_twice(): void
    {
        $product = $this->createProduct();
        $completeness = new ProductCompleteness();

        $product->addCompleteness($completeness);
        $product->addCompleteness($completeness);

        self::assertCount(1, $product->getCompletenesses());
    }

    /**
     * @test
     */
    public function it_removes_a_completeness_and_unsets_the_owning_side(): void
    {
        $product = $this->createProduct();
        $completeness = new ProductCompleteness();
        $product->addCompleteness($completeness);

        $product->removeCompleteness($completeness);

        self::assertFalse($product->hasCompleteness($completeness));
        self::assertNull($completeness->getProduct());
    }

    /**
     * @test
     */
    public function it_stores_ratio_and_rubric_version(): void
    {
        $product = $this->createProduct();

        $product->setCompletenessRatio(87);
        $product->setCompletenessRubricVersion(3);

        self::assertSame(87, $product->getCompletenessRatio());
        self::assertSame(3, $product->getCompletenessRubricVersion());
    }

    private function createProduct(): ProductCompletenessAwareInterface&BaseProduct
    {
        return new class() extends BaseProduct implements ProductCompletenessAwareInterface {
            use ProductCompletenessAwareTrait;
        };
    }
}

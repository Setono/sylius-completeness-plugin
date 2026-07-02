<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Checker;

use Setono\SyliusCompletenessPlugin\Checker\HasMinimumImagesChecker;
use Setono\SyliusCompletenessPlugin\Exception\InvalidCheckerConfigurationException;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductImage;

final class HasMinimumImagesCheckerTest extends CheckerTestCase
{
    /**
     * @test
     */
    public function it_grants_partial_credit(): void
    {
        self::assertSame(0.6, (new HasMinimumImagesChecker())->score(
            $this->createProductWithImages(3),
            $this->createContext(),
            ['count' => 5],
        ));
    }

    /**
     * @test
     */
    public function it_caps_the_score_at_one(): void
    {
        self::assertSame(1.0, (new HasMinimumImagesChecker())->score(
            $this->createProductWithImages(7),
            $this->createContext(),
            ['count' => 5],
        ));
    }

    /**
     * @test
     */
    public function it_scores_zero_when_the_product_has_no_images(): void
    {
        self::assertSame(0.0, (new HasMinimumImagesChecker())->score(
            $this->createProductWithImages(0),
            $this->createContext(),
            ['count' => 5],
        ));
    }

    /**
     * @test
     */
    public function it_throws_when_the_count_is_missing(): void
    {
        $this->expectException(InvalidCheckerConfigurationException::class);

        (new HasMinimumImagesChecker())->score($this->createProductWithImages(3), $this->createContext(), []);
    }

    /**
     * @test
     */
    public function it_throws_when_the_count_is_less_than_one(): void
    {
        $this->expectException(InvalidCheckerConfigurationException::class);

        (new HasMinimumImagesChecker())->score($this->createProductWithImages(3), $this->createContext(), ['count' => 0]);
    }

    private function createProductWithImages(int $images): Product
    {
        $product = $this->createProduct();
        for ($i = 0; $i < $images; ++$i) {
            $product->addImage(new ProductImage());
        }

        return $product;
    }
}

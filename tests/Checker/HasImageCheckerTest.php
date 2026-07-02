<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Checker;

use Setono\SyliusCompletenessPlugin\Checker\HasImageChecker;
use Sylius\Component\Core\Model\ProductImage;

final class HasImageCheckerTest extends CheckerTestCase
{
    /**
     * @test
     */
    public function it_scores_one_when_the_product_has_an_image(): void
    {
        $product = $this->createProduct();
        $product->addImage(new ProductImage());

        self::assertSame(1.0, (new HasImageChecker())->score($product, $this->createContext(), []));
    }

    /**
     * @test
     */
    public function it_scores_zero_when_the_product_has_no_images(): void
    {
        self::assertSame(0.0, (new HasImageChecker())->score($this->createProduct(), $this->createContext(), []));
    }
}

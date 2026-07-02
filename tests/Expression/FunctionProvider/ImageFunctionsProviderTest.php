<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Expression\FunctionProvider;

use Sylius\Component\Core\Model\ProductImage;

final class ImageFunctionsProviderTest extends FunctionProviderTestCase
{
    /**
     * @test
     */
    public function it_counts_images(): void
    {
        $product = $this->createProduct();
        self::assertSame(0, $this->evaluate('image_count(product)', ['product' => $product]));
        self::assertFalse($this->evaluate('has_image(product)', ['product' => $product]));

        $image = new ProductImage();
        $image->setType('main');
        $product->addImage($image);

        self::assertSame(1, $this->evaluate('image_count(product)', ['product' => $product]));
        self::assertTrue($this->evaluate('has_image(product)', ['product' => $product]));
    }

    /**
     * @test
     */
    public function it_counts_images_of_a_type(): void
    {
        $product = $this->createProduct();

        $main = new ProductImage();
        $main->setType('main');
        $product->addImage($main);

        $thumbnail = new ProductImage();
        $thumbnail->setType('thumbnail');
        $product->addImage($thumbnail);

        self::assertSame(1, $this->evaluate('image_count_of_type(product, "main")', ['product' => $product]));
        self::assertTrue($this->evaluate('has_image_type(product, "thumbnail")', ['product' => $product]));
        self::assertFalse($this->evaluate('has_image_type(product, "packshot")', ['product' => $product]));
    }
}

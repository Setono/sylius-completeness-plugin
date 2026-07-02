<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Checker;

use Setono\SyliusCompletenessPlugin\Checker\HasDescriptionChecker;

final class HasDescriptionCheckerTest extends CheckerTestCase
{
    /**
     * @test
     */
    public function it_scores_one_when_the_description_is_set(): void
    {
        $product = $this->createProduct();
        $product->setDescription('<p>A very nice shirt</p>');

        self::assertSame(1.0, (new HasDescriptionChecker())->score($product, $this->createContext(), []));
    }

    /**
     * @test
     */
    public function it_scores_zero_when_the_description_is_html_without_text(): void
    {
        $product = $this->createProduct();
        $product->setDescription('<p>&nbsp;</p><br/>');

        self::assertSame(0.0, (new HasDescriptionChecker())->score($product, $this->createContext(), []));
    }

    /**
     * @test
     */
    public function it_scores_zero_when_the_description_only_exists_in_another_locale(): void
    {
        $product = $this->createProduct('en');
        $product->setDescription('An English description');

        $product->setCurrentLocale('da');
        $product->setFallbackLocale('da');

        self::assertSame(0.0, (new HasDescriptionChecker())->score($product, $this->createContext('WEB', 'da'), []));
    }
}

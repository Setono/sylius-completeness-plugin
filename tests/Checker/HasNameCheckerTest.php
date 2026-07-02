<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Checker;

use Setono\SyliusCompletenessPlugin\Checker\HasNameChecker;

final class HasNameCheckerTest extends CheckerTestCase
{
    /**
     * @test
     */
    public function it_scores_one_when_the_name_is_set_for_the_context_locale(): void
    {
        $product = $this->createProduct('da');
        $product->setName('Trøje');

        self::assertSame(1.0, (new HasNameChecker())->score($product, $this->createContext('WEB', 'da'), []));
    }

    /**
     * @test
     */
    public function it_scores_zero_when_the_name_only_exists_in_another_locale(): void
    {
        $product = $this->createProduct('en');
        $product->setName('Shirt');

        // the calculator sets current + fallback locale to the context locale before checking
        $product->setCurrentLocale('da');
        $product->setFallbackLocale('da');

        self::assertSame(0.0, (new HasNameChecker())->score($product, $this->createContext('WEB', 'da'), []));
    }

    /**
     * @test
     */
    public function it_scores_zero_when_the_name_is_blank(): void
    {
        $product = $this->createProduct();
        $product->setName('   ');

        self::assertSame(0.0, (new HasNameChecker())->score($product, $this->createContext(), []));
    }
}

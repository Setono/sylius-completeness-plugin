<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Expression\FunctionProvider;

use Setono\SyliusCompletenessPlugin\Exception\NoActiveCalculationException;

final class TranslationFunctionsProviderTest extends FunctionProviderTestCase
{
    /**
     * @test
     */
    public function it_uses_the_calculation_context_locale_by_default(): void
    {
        $product = $this->createProduct('da');
        $product->setName('Trøje');

        $this->publishContext('WEB', 'da');
        self::assertTrue($this->evaluate('has_translation(product)', ['product' => $product]));

        $this->publishContext('WEB', 'sv');
        self::assertFalse($this->evaluate('has_translation(product)', ['product' => $product]));
    }

    /**
     * @test
     */
    public function it_accepts_an_explicit_locale_override(): void
    {
        $product = $this->createProduct('da');
        $product->setName('Trøje');

        $this->publishContext('WEB', 'sv');

        self::assertTrue($this->evaluate('has_translation(product, "da")', ['product' => $product]));
    }

    /**
     * @test
     */
    public function it_throws_outside_a_calculation_when_no_locale_is_given(): void
    {
        $this->expectException(NoActiveCalculationException::class);

        $this->evaluate('has_translation(product)', ['product' => $this->createProduct()]);
    }
}

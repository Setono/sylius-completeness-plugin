<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Preview;

use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\Calculator\ContextInitializer;
use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Setono\SyliusCompletenessPlugin\Context\CalculationContext;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionEvaluator;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionLanguageFactory;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\CollectionFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\TextFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Preview\ScratchpadEvaluator;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Locale\Model\Locale;

final class ScratchpadEvaluatorTest extends TestCase
{
    private ScratchpadEvaluator $evaluator;

    protected function setUp(): void
    {
        $calculationContext = new CalculationContext();

        $this->evaluator = new ScratchpadEvaluator(
            new ContextInitializer($calculationContext),
            new ExpressionEvaluator(ExpressionLanguageFactory::create([
                new TextFunctionsProvider(),
                new CollectionFunctionsProvider(),
            ])),
        );
    }

    private function createContext(): CompletenessCheckContext
    {
        $channel = new Channel();
        $channel->setCode('WEB');

        $locale = new Locale();
        $locale->setCode('en');

        return new CompletenessCheckContext($channel, $locale);
    }

    private function createProduct(): Product
    {
        $product = new Product();
        $product->setCurrentLocale('en');
        $product->setFallbackLocale('en');

        return $product;
    }

    /**
     * @test
     */
    public function it_interprets_a_boolean_result_as_a_score(): void
    {
        $product = $this->createProduct();
        $product->setName('Shirt');

        $result = $this->evaluator->evaluate($product, $this->createContext(), 'product.getName() != ""');

        self::assertFalse($result->errored);
        self::assertTrue($result->rawValue);
        self::assertSame(1.0, $result->score);
    }

    /**
     * @test
     */
    public function it_interprets_a_number_result_as_a_score(): void
    {
        $product = $this->createProduct();
        $product->setDescription('one two three');

        $result = $this->evaluator->evaluate($product, $this->createContext(), 'min(word_count(product.getDescription()) / 2, 1)');

        self::assertFalse($result->errored);
        self::assertSame(1.0, $result->score);
    }

    /**
     * @test
     */
    public function it_returns_a_raw_value_without_a_score_for_a_non_numeric_result(): void
    {
        $result = $this->evaluator->evaluate($this->createProduct(), $this->createContext(), '"a string"');

        self::assertFalse($result->errored);
        self::assertSame('a string', $result->rawValue);
        self::assertNull($result->score);
    }

    /**
     * @test
     */
    public function it_reports_a_compile_error(): void
    {
        $result = $this->evaluator->evaluate($this->createProduct(), $this->createContext(), 'unknown_function(product)');

        self::assertTrue($result->errored);
        self::assertNotNull($result->error);
        self::assertNull($result->score);
    }
}

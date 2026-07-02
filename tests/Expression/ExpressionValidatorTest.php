<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Expression;

use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\Context\CalculationContext;
use Setono\SyliusCompletenessPlugin\Exception\InvalidExpressionException;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionLanguageFactory;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionValidator;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\TextFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\TranslationFunctionsProvider;

final class ExpressionValidatorTest extends TestCase
{
    private ExpressionValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ExpressionValidator(ExpressionLanguageFactory::create([
            new TextFunctionsProvider(),
            new TranslationFunctionsProvider(new CalculationContext()),
        ]));
    }

    /**
     * @test
     */
    public function it_accepts_a_valid_expression(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator->validate('word_count(product.getDescription()) >= 200');
    }

    /**
     * @test
     */
    public function it_rejects_an_unknown_function(): void
    {
        $this->expectException(InvalidExpressionException::class);

        $this->validator->validate('unknown_function(product)');
    }

    /**
     * @test
     */
    public function it_rejects_an_unknown_variable(): void
    {
        $this->expectException(InvalidExpressionException::class);

        $this->validator->validate('foo == 1');
    }

    /**
     * @test
     */
    public function it_rejects_a_syntax_error_and_includes_the_reason(): void
    {
        try {
            $this->validator->validate('product.name matches');
            self::fail('Expected an InvalidExpressionException to be thrown');
        } catch (InvalidExpressionException $e) {
            self::assertStringContainsString('product.name matches', $e->getMessage());
        }
    }
}

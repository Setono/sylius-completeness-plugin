<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Checker;

use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusCompletenessPlugin\Checker\ExpressionChecker;
use Setono\SyliusCompletenessPlugin\Exception\InvalidCheckerConfigurationException;
use Setono\SyliusCompletenessPlugin\Exception\UnexpectedExpressionResultException;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionEvaluatorInterface;

final class ExpressionCheckerTest extends CheckerTestCase
{
    use ProphecyTrait;

    /**
     * @test
     *
     * @dataProvider resultToScoreProvider
     */
    public function it_interprets_the_expression_result(mixed $result, float $expectedScore): void
    {
        $product = $this->createProduct();
        $context = $this->createContext();

        $evaluator = $this->prophesize(ExpressionEvaluatorInterface::class);
        $evaluator->evaluate('some_expression()', $product, $context)->willReturn($result);

        self::assertSame(
            $expectedScore,
            (new ExpressionChecker($evaluator->reveal()))->score($product, $context, ['expression' => 'some_expression()']),
        );
    }

    /**
     * @return iterable<string, array{mixed, float}>
     */
    public static function resultToScoreProvider(): iterable
    {
        yield 'true maps to 1.0' => [true, 1.0];

        yield 'false maps to 0.0' => [false, 0.0];

        yield 'a float is used directly' => [0.5, 0.5];

        yield 'an int is cast to float' => [1, 1.0];

        yield 'an out of range number is passed through unclamped (the calculator clamps)' => [2.5, 2.5];
    }

    /**
     * @test
     *
     * @dataProvider unexpectedResultProvider
     */
    public function it_throws_when_the_expression_returns_something_else_than_a_boolean_or_number(mixed $result): void
    {
        $product = $this->createProduct();
        $context = $this->createContext();

        $evaluator = $this->prophesize(ExpressionEvaluatorInterface::class);
        $evaluator->evaluate('some_expression()', $product, $context)->willReturn($result);

        $this->expectException(UnexpectedExpressionResultException::class);

        (new ExpressionChecker($evaluator->reveal()))->score($product, $context, ['expression' => 'some_expression()']);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function unexpectedResultProvider(): iterable
    {
        yield 'string' => ['foo'];

        yield 'null' => [null];

        yield 'array' => [[1, 2]];
    }

    /**
     * @test
     */
    public function it_throws_when_the_expression_configuration_is_missing(): void
    {
        $evaluator = $this->prophesize(ExpressionEvaluatorInterface::class);

        $this->expectException(InvalidCheckerConfigurationException::class);

        (new ExpressionChecker($evaluator->reveal()))->score($this->createProduct(), $this->createContext(), []);
    }
}

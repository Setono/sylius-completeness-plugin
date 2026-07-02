<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Expression\FunctionProvider;

final class CollectionFunctionsProviderTest extends FunctionProviderTestCase
{
    /**
     * @test
     */
    public function it_counts(): void
    {
        self::assertSame(2, $this->evaluate('count(value)', ['value' => [1, 2]]));
        self::assertSame(0, $this->evaluate('count(value)', ['value' => null]));
        self::assertSame(3, $this->evaluate('count(value)', ['value' => new \ArrayIterator([1, 2, 3])]));
    }

    /**
     * @test
     */
    public function it_throws_when_counting_an_uncountable_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->evaluate('count(value)', ['value' => 42]);
    }

    /**
     * @test
     */
    public function it_checks_emptiness(): void
    {
        self::assertTrue($this->evaluate('is_empty(value)', ['value' => []]));
        self::assertTrue($this->evaluate('is_empty(value)', ['value' => null]));
        self::assertTrue($this->evaluate('is_empty(value)', ['value' => ' ']));
        self::assertFalse($this->evaluate('is_empty(value)', ['value' => [1]]));
        self::assertFalse($this->evaluate('is_empty(value)', ['value' => 'x']));
    }

    /**
     * @test
     */
    public function it_computes_min_and_max(): void
    {
        self::assertSame(1, $this->evaluate('min(1, 2)'));
        self::assertSame(2, $this->evaluate('max(1, 2)'));
        self::assertSame(0.5, $this->evaluate('min(0.5, 1)'));
    }

    /**
     * @test
     */
    public function it_checks_between_inclusively(): void
    {
        self::assertTrue($this->evaluate('between(5, 1, 10)'));
        self::assertTrue($this->evaluate('between(1, 1, 10)'));
        self::assertTrue($this->evaluate('between(10, 1, 10)'));
        self::assertFalse($this->evaluate('between(11, 1, 10)'));
    }

    /**
     * @test
     */
    public function it_supports_the_graded_expression_idiom(): void
    {
        // the docs example: min(word_count(product.description) / 200, 1)
        self::assertSame(0.5, $this->evaluate('min(word_count(text) / 4, 1)', ['text' => 'one two']));
        self::assertSame(1, $this->evaluate('min(word_count(text) / 2, 1)', ['text' => 'one two three']));
    }
}

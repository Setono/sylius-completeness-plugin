<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Expression;

use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionFunctionNameProvider;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

final class ExpressionFunctionNameProviderTest extends TestCase
{
    /**
     * @test
     */
    public function it_collects_sorted_and_deduplicated_names_from_all_providers(): void
    {
        $provider = new ExpressionFunctionNameProvider([
            self::provider('word_count', 'has_price'),
            self::provider('attribute_value', 'word_count'),
        ]);

        self::assertSame(
            ['attribute_value', 'has_price', 'word_count'],
            $provider->getNames(),
        );
    }

    /**
     * @test
     */
    public function it_returns_an_empty_list_when_there_are_no_providers(): void
    {
        self::assertSame([], (new ExpressionFunctionNameProvider([]))->getNames());
    }

    private static function provider(string ...$names): ExpressionFunctionProviderInterface
    {
        $functions = array_values(array_map(
            static fn (string $name): ExpressionFunction => new ExpressionFunction(
                $name,
                static fn (): string => '',
                static fn (): mixed => null,
            ),
            $names,
        ));

        return new class($functions) implements ExpressionFunctionProviderInterface {
            /**
             * @param list<ExpressionFunction> $functions
             */
            public function __construct(private readonly array $functions)
            {
            }

            public function getFunctions(): array
            {
                return $this->functions;
            }
        };
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Expression;

use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\Expression\DocumentedExpressionFunction;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionFunctionDocumentationProvider;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

final class ExpressionFunctionDocumentationProviderTest extends TestCase
{
    /**
     * @test
     */
    public function it_derives_documentation_from_the_documented_functions(): void
    {
        $provider = new ExpressionFunctionDocumentationProvider([
            $this->functionProvider(
                new DocumentedExpressionFunction('word_count', $this->noop(), $this->noop(), 'word_count(text): int', 'Counts words.'),
            ),
            $this->functionProvider(
                new DocumentedExpressionFunction('has_image', $this->noop(), $this->noop(), 'has_image(product): bool', 'True when there is an image.'),
            ),
        ]);

        self::assertSame([
            'word_count' => ['signature' => 'word_count(text): int', 'description' => 'Counts words.'],
            'has_image' => ['signature' => 'has_image(product): bool', 'description' => 'True when there is an image.'],
        ], $provider->getDocumentation());
    }

    /**
     * @test
     */
    public function it_ignores_functions_that_carry_no_documentation(): void
    {
        $provider = new ExpressionFunctionDocumentationProvider([
            $this->functionProvider(
                new DocumentedExpressionFunction('word_count', $this->noop(), $this->noop(), 'word_count(text): int', 'Counts words.'),
                new ExpressionFunction('undocumented', $this->noop(), $this->noop()),
            ),
        ]);

        self::assertSame(['word_count'], array_keys($provider->getDocumentation()));
    }

    /**
     * @test
     */
    public function it_returns_nothing_when_there_are_no_providers(): void
    {
        self::assertSame([], (new ExpressionFunctionDocumentationProvider([]))->getDocumentation());
    }

    private function functionProvider(ExpressionFunction ...$functions): ExpressionFunctionProviderInterface
    {
        return new class(array_values($functions)) implements ExpressionFunctionProviderInterface {
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

    private function noop(): \Closure
    {
        return static fn () => null;
    }
}

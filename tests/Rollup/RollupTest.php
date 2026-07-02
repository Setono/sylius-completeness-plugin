<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Rollup;

use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\Calculator\Result\ContextResult;
use Setono\SyliusCompletenessPlugin\Rollup\Rollup;
use Setono\SyliusCompletenessPlugin\Rollup\WeightedAverageRollupStrategy;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class RollupTest extends TestCase
{
    private function createRollup(string $strategy = 'weighted_average'): Rollup
    {
        return new Rollup(
            new ServiceLocator([
                'weighted_average' => static fn (): WeightedAverageRollupStrategy => new WeightedAverageRollupStrategy(),
            ]),
            $strategy,
        );
    }

    private function createContextResult(?int $ratio, float $rollupWeight = 1.0, bool $excluded = false): ContextResult
    {
        return new ContextResult(
            channelCode: 'WEB',
            localeCode: 'en',
            ratio: $ratio,
            weightedPassed: 0.0,
            weightedTotal: 0.0,
            groupScores: [],
            ruleResults: [],
            rollupWeight: $rollupWeight,
            excluded: $excluded,
        );
    }

    /**
     * @test
     */
    public function it_drops_na_and_excluded_contexts_before_delegating(): void
    {
        $result = $this->createRollup()->rollup([
            $this->createContextResult(100),
            $this->createContextResult(null), // N/A
            $this->createContextResult(0, 0.0, true), // excluded
        ]);

        self::assertSame(100, $result);
    }

    /**
     * @test
     */
    public function it_returns_null_when_every_context_is_na_or_excluded(): void
    {
        self::assertNull($this->createRollup()->rollup([
            $this->createContextResult(null),
            $this->createContextResult(50, 0.0, true),
        ]));
    }

    /**
     * @test
     */
    public function it_returns_null_for_no_contexts(): void
    {
        self::assertNull($this->createRollup()->rollup([]));
    }

    /**
     * @test
     */
    public function it_throws_for_an_unknown_strategy(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/nonexistent/');

        $this->createRollup('nonexistent')->rollup([$this->createContextResult(100)]);
    }
}

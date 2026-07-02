<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Calculator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\Calculator\RuleWeightResolver;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRule;

final class RuleWeightResolverTest extends TestCase
{
    private RuleWeightResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new RuleWeightResolver(['low' => 1.0, 'medium' => 3.0, 'high' => 6.0, 'critical' => 10.0]);
    }

    /**
     * @test
     */
    public function it_resolves_the_weight_from_the_tier(): void
    {
        $rule = new CompletenessRule();
        $rule->setWeightTier('high');

        self::assertSame(6.0, $this->resolver->resolve($rule));
    }

    /**
     * @test
     */
    public function it_prefers_the_custom_weight(): void
    {
        $rule = new CompletenessRule();
        $rule->setWeightTier('high');
        $rule->setCustomWeight(4.5);

        self::assertSame(4.5, $this->resolver->resolve($rule));
    }

    /**
     * @test
     */
    public function it_throws_for_an_unknown_tier(): void
    {
        $rule = new CompletenessRule();
        $rule->setWeightTier('galactic');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/galactic/');

        $this->resolver->resolve($rule);
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusCompletenessPlugin\Calculator\RuleWeightResolver;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRule;
use Setono\SyliusCompletenessPlugin\Repository\CompletenessRuleRepositoryInterface;
use Setono\SyliusCompletenessPlugin\Twig\CompletenessDisplayRuntime;

final class CompletenessDisplayRuntimeTest extends TestCase
{
    use ProphecyTrait;

    private function createRule(string $tier, bool $enabled = true): CompletenessRule
    {
        $rule = new CompletenessRule();
        $rule->setWeightTier($tier);
        $rule->setEnabled($enabled);

        return $rule;
    }

    /**
     * @test
     */
    public function it_computes_the_share_and_memoizes_the_total(): void
    {
        $critical = $this->createRule('critical');
        $medium = $this->createRule('medium');
        $low = $this->createRule('low');

        $repository = $this->prophesize(CompletenessRuleRepositoryInterface::class);
        $repository->findEnabled()->willReturn([$critical, $medium, $low])->shouldBeCalledOnce();

        $runtime = new CompletenessDisplayRuntime(
            $repository->reveal(),
            new RuleWeightResolver(['low' => 1.0, 'medium' => 3.0, 'high' => 6.0, 'critical' => 10.0]),
            [],
        );

        // total = 14
        self::assertEqualsWithDelta(10 / 14, $runtime->ruleShare($critical), 0.0001);
        self::assertEqualsWithDelta(3 / 14, $runtime->ruleShare($medium), 0.0001);
        self::assertEqualsWithDelta(1 / 14, $runtime->ruleShare($low), 0.0001);
    }

    /**
     * @test
     */
    public function it_returns_zero_for_disabled_rules(): void
    {
        $repository = $this->prophesize(CompletenessRuleRepositoryInterface::class);
        $repository->findEnabled()->willReturn([]);

        $runtime = new CompletenessDisplayRuntime(
            $repository->reveal(),
            new RuleWeightResolver(['medium' => 3.0]),
            [],
        );

        self::assertSame(0.0, $runtime->ruleShare($this->createRule('medium', enabled: false)));
    }

    /**
     * @test
     */
    public function it_resolves_checker_labels_with_a_fallback(): void
    {
        $repository = $this->prophesize(CompletenessRuleRepositoryInterface::class);
        $repository->findEnabled()->willReturn([]);

        $runtime = new CompletenessDisplayRuntime(
            $repository->reveal(),
            new RuleWeightResolver([]),
            ['has_name' => 'setono_sylius_completeness.ui.checker.has_name'],
        );

        self::assertSame('setono_sylius_completeness.ui.checker.has_name', $runtime->checkerLabel('has_name'));
        self::assertSame('custom_type', $runtime->checkerLabel('custom_type'));
    }
}

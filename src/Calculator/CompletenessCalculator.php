<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Calculator;

use Psr\Clock\ClockInterface;
use Setono\SyliusCompletenessPlugin\Calculator\Result\ContextResult;
use Setono\SyliusCompletenessPlugin\Calculator\Result\GroupScore;
use Setono\SyliusCompletenessPlugin\Calculator\Result\ProductCompletenessResult;
use Setono\SyliusCompletenessPlugin\Calculator\Result\RuleResult;
use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckerInterface;
use Setono\SyliusCompletenessPlugin\Checker\ExpressionChecker;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRuleInterface;
use Setono\SyliusCompletenessPlugin\Provider\ContextSettingsProviderInterface;
use Setono\SyliusCompletenessPlugin\Repository\CompletenessRuleRepositoryInterface;
use Setono\SyliusCompletenessPlugin\Rollup\RollupInterface;
use Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManagerInterface;
use Sylius\Component\Channel\Model\ChannelInterface as BaseChannelInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Registry\ServiceRegistryInterface;

final class CompletenessCalculator implements CompletenessCalculatorInterface
{
    public function __construct(
        private readonly CompletenessRuleRepositoryInterface $ruleRepository,
        private readonly ServiceRegistryInterface $checkerRegistry,
        private readonly RuleApplicabilityCheckerInterface $applicabilityChecker,
        private readonly RuleWeightResolverInterface $weightResolver,
        private readonly ContextInitializerInterface $contextInitializer,
        private readonly RollupInterface $rollup,
        private readonly ContextSettingsProviderInterface $contextSettings,
        private readonly RubricVersionManagerInterface $rubricVersionManager,
        private readonly ClockInterface $clock,
    ) {
    }

    public function calculate(ProductInterface $product): ProductCompletenessResult
    {
        $rules = $this->ruleRepository->findEnabled();

        $contextResults = [];

        try {
            foreach ($this->resolveContexts($product) as $context) {
                $contextResults[] = $this->doCalculateContext($product, $context, $rules);
            }
        } finally {
            $this->contextInitializer->terminate();
        }

        return new ProductCompletenessResult(
            globalRatio: $this->rollup->rollup($contextResults),
            contextResults: $contextResults,
            rubricVersion: $this->rubricVersionManager->getCurrentVersion(),
            calculatedAt: $this->clock->now(),
        );
    }

    public function calculateContext(ProductInterface $product, CompletenessCheckContext $context): ContextResult
    {
        try {
            return $this->doCalculateContext($product, $context, $this->ruleRepository->findEnabled());
        } finally {
            $this->contextInitializer->terminate();
        }
    }

    /**
     * Derives the product's contexts as its channels × each channel's locales, deduplicated
     * by (channel code, locale code). Channels or locales without a code are skipped
     *
     * @return list<CompletenessCheckContext>
     */
    private function resolveContexts(ProductInterface $product): array
    {
        $contexts = [];

        /** @var BaseChannelInterface $channel */
        foreach ($product->getChannels() as $channel) {
            if (!$channel instanceof ChannelInterface || null === $channel->getCode()) {
                continue;
            }

            foreach ($channel->getLocales() as $locale) {
                if (null === $locale->getCode()) {
                    continue;
                }

                $contexts[$channel->getCode() . '|' . $locale->getCode()] ??= new CompletenessCheckContext($channel, $locale);
            }
        }

        return array_values($contexts);
    }

    /**
     * @param list<CompletenessRuleInterface> $rules
     */
    private function doCalculateContext(ProductInterface $product, CompletenessCheckContext $context, array $rules): ContextResult
    {
        $this->contextInitializer->initialize($product, $context);

        $ruleResults = [];
        foreach ($rules as $rule) {
            $applicability = $this->applicabilityChecker->check($rule, $product, $context);
            if (!$applicability->applies) {
                continue;
            }

            $ruleResults[] = $this->evaluateRule($rule, $product, $context, $applicability);
        }

        $weightedPassed = 0.0;
        $weightedTotal = 0.0;

        /** @var array<string, array{group: ?string, passed: float, total: float}> $groups */
        $groups = [];

        foreach ($ruleResults as $ruleResult) {
            $weightedTotal += $ruleResult->weight;
            $weightedPassed += $ruleResult->weight * $ruleResult->score;

            $groupKey = $ruleResult->group ?? "\0ungrouped";
            $groups[$groupKey] ??= ['group' => $ruleResult->group, 'passed' => 0.0, 'total' => 0.0];
            $groups[$groupKey]['passed'] += $ruleResult->weight * $ruleResult->score;
            $groups[$groupKey]['total'] += $ruleResult->weight;
        }

        $groupScores = [];
        foreach ($groups as ['group' => $group, 'passed' => $groupPassed, 'total' => $groupTotal]) {
            $groupScores[] = new GroupScore(
                group: $group,
                ratio: self::ratio($groupPassed, $groupTotal),
                weightedPassed: $groupPassed,
                weightedTotal: $groupTotal,
            );
        }

        $rollupWeight = $this->contextSettings->getRollupWeight($context->getChannelCode(), $context->getLocaleCode());

        return new ContextResult(
            channelCode: $context->getChannelCode(),
            localeCode: $context->getLocaleCode(),
            ratio: self::ratio($weightedPassed, $weightedTotal),
            weightedPassed: $weightedPassed,
            weightedTotal: $weightedTotal,
            groupScores: $groupScores,
            ruleResults: $ruleResults,
            rollupWeight: $rollupWeight,
            excluded: 0.0 === $rollupWeight,
        );
    }

    private function evaluateRule(
        CompletenessRuleInterface $rule,
        ProductInterface $product,
        CompletenessCheckContext $context,
        Applicability $applicability,
    ): RuleResult {
        $code = (string) $rule->getCode();
        $label = (string) $rule->getLabel();
        $group = $rule->getGroup();
        $type = (string) $rule->getType();

        try {
            $weight = $this->weightResolver->resolve($rule);
        } catch (\Throwable $e) {
            // without a resolvable weight the rule cannot participate in the math, so it is
            // surfaced as errored with weight 0 (present in the breakdown, neutral to the score)
            return new RuleResult($code, $label, $group, $type, 0.0, 0.0, true, $e->getMessage());
        }

        if ($applicability->errored) {
            return new RuleResult($code, $label, $group, $type, $weight, 0.0, true, $applicability->error);
        }

        try {
            if (!$this->checkerRegistry->has($type)) {
                throw new \RuntimeException(sprintf('No completeness checker is registered for the type "%s"', $type));
            }

            $checker = $this->checkerRegistry->get($type);
            if (!$checker instanceof CompletenessCheckerInterface) {
                throw new \RuntimeException(sprintf('The checker for type "%s" does not implement %s', $type, CompletenessCheckerInterface::class));
            }

            $configuration = $rule->getConfiguration();
            if (ExpressionChecker::TYPE === $type) {
                $configuration['expression'] = $rule->getExpression();
            }

            $score = $checker->score($product, $context, $configuration);

            return new RuleResult($code, $label, $group, $type, $weight, max(0.0, min(1.0, $score)), false, null);
        } catch (\Throwable $e) {
            return new RuleResult($code, $label, $group, $type, $weight, 0.0, true, $e->getMessage());
        }
    }

    private static function ratio(float $weightedPassed, float $weightedTotal): ?int
    {
        if ($weightedTotal <= 0.0) {
            return null;
        }

        return (int) round(100 * $weightedPassed / $weightedTotal);
    }
}

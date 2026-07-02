<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Calculator\Result;

/**
 * The complete outcome of evaluating a product in one (channel, locale) context
 */
final class ContextResult
{
    public function __construct(
        public readonly string $channelCode,
        public readonly string $localeCode,
        /** Null means N/A: no rules applied in this context */
        public readonly ?int $ratio,
        public readonly float $weightedPassed,
        public readonly float $weightedTotal,
        /** @var list<GroupScore> */
        public readonly array $groupScores,
        /** @var list<RuleResult> all applying rules, including fully met ones (the preview shows passes too) */
        public readonly array $ruleResults,
        /** The context's weight in the global rollup */
        public readonly float $rollupWeight,
        /** True when the context is excluded from the global rollup (rollup weight 0) */
        public readonly bool $excluded,
    ) {
    }

    /**
     * @return list<RuleResult>
     */
    public function getUnmetRuleResults(): array
    {
        return array_values(array_filter(
            $this->ruleResults,
            static fn (RuleResult $ruleResult): bool => $ruleResult->isUnmet(),
        ));
    }
}

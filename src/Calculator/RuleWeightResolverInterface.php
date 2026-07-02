<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Calculator;

use Setono\SyliusCompletenessPlugin\Model\CompletenessRuleInterface;

interface RuleWeightResolverInterface
{
    /**
     * Resolves the effective weight of a rule: the custom weight when set, otherwise the
     * configured float for the rule's weight tier
     *
     * @throws \RuntimeException when the rule's weight tier is not configured
     */
    public function resolve(CompletenessRuleInterface $rule): float;
}

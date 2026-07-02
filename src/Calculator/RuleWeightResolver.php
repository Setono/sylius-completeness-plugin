<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Calculator;

use Setono\SyliusCompletenessPlugin\Model\CompletenessRuleInterface;

final class RuleWeightResolver implements RuleWeightResolverInterface
{
    /**
     * @param array<string, float> $weightTiers
     */
    public function __construct(private readonly array $weightTiers)
    {
    }

    public function resolve(CompletenessRuleInterface $rule): float
    {
        $customWeight = $rule->getCustomWeight();
        if (null !== $customWeight) {
            return $customWeight;
        }

        $tier = $rule->getWeightTier();

        return $this->weightTiers[$tier] ?? throw new \RuntimeException(sprintf(
            'The weight tier "%s" is not configured. Configured tiers: %s',
            $tier,
            implode(', ', array_keys($this->weightTiers)),
        ));
    }
}

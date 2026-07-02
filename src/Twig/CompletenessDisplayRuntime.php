<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Twig;

use Setono\SyliusCompletenessPlugin\Calculator\RuleWeightResolverInterface;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRuleInterface;
use Setono\SyliusCompletenessPlugin\Repository\CompletenessRuleRepositoryInterface;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class CompletenessDisplayRuntime implements RuntimeExtensionInterface, ResetInterface
{
    private ?float $totalWeight = null;

    /**
     * @param array<string, string> $checkers checker type => label
     */
    public function __construct(
        private readonly CompletenessRuleRepositoryInterface $ruleRepository,
        private readonly RuleWeightResolverInterface $weightResolver,
        private readonly array $checkers,
    ) {
    }

    /**
     * Returns this rule's share (0.0-1.0) of the total resolved weight of all enabled rules.
     * Notice that this is a legibility aid computed against the whole enabled set - a scoped
     * rule's real share within the contexts it applies to will differ
     */
    public function ruleShare(CompletenessRuleInterface $rule): float
    {
        if (!$rule->isEnabled()) {
            return 0.0;
        }

        $totalWeight = $this->totalWeight ??= $this->calculateTotalWeight();
        if ($totalWeight <= 0.0) {
            return 0.0;
        }

        return $this->resolveWeightSafely($rule) / $totalWeight;
    }

    public function checkerLabel(string $type): string
    {
        return $this->checkers[$type] ?? $type;
    }

    public function reset(): void
    {
        $this->totalWeight = null;
    }

    private function calculateTotalWeight(): float
    {
        $totalWeight = 0.0;
        foreach ($this->ruleRepository->findEnabled() as $rule) {
            $totalWeight += $this->resolveWeightSafely($rule);
        }

        return $totalWeight;
    }

    private function resolveWeightSafely(CompletenessRuleInterface $rule): float
    {
        try {
            return $this->weightResolver->resolve($rule);
        } catch (\Throwable) {
            return 0.0;
        }
    }
}

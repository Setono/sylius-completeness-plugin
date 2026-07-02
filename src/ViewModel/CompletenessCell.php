<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\ViewModel;

/**
 * One (channel, locale) cell of the breakdown matrix
 */
final class CompletenessCell
{
    /**
     * @param list<array{group: ?string, ratio: ?int, weightedPassed: float, weightedTotal: float}> $groupScores
     * @param list<array{group: ?string, rules: list<array{code: string, label: string, group: ?string, checkerType: string, weight: float, score: float, errored: bool, error?: ?string}>}> $unmetRuleGroups unmet rules grouped by group, each group's rules ordered by weight desc
     */
    public function __construct(
        public readonly string $channelCode,
        public readonly string $localeCode,
        public readonly ?int $ratio,
        public readonly int $threshold,
        public readonly string $color,
        public readonly bool $excluded,
        public readonly array $groupScores,
        public readonly array $unmetRuleGroups,
        public readonly ?\DateTimeImmutable $calculatedAt,
    ) {
    }

    public function isNotApplicable(): bool
    {
        return null === $this->ratio;
    }
}

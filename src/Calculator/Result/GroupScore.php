<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Calculator\Result;

/**
 * The weighted sub score of one rule group within a (channel, locale) context
 */
final class GroupScore
{
    public function __construct(
        /** Null means the implicit "ungrouped" bucket */
        public readonly ?string $group,
        public readonly ?int $ratio,
        public readonly float $weightedPassed,
        public readonly float $weightedTotal,
    ) {
    }
}

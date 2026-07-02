<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Calculator\Result;

/**
 * The outcome of a single applying rule within one (channel, locale) context
 */
final class RuleResult
{
    public function __construct(
        public readonly string $code,
        public readonly string $label,
        public readonly ?string $group,
        public readonly string $checkerType,
        /** The resolved weight of the rule ("how much it matters") */
        public readonly float $weight,
        /** The clamped checker score in [0, 1] ("how met it is"). Errored rules score 0 */
        public readonly float $score,
        public readonly bool $errored,
        public readonly ?string $error = null,
    ) {
    }

    public function isUnmet(): bool
    {
        return $this->errored || $this->score < 1.0;
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Calculator;

/**
 * The result of gating a rule against a (product, channel, locale). A skipped rule is absent
 * from both the numerator and denominator. A rule whose condition THREW is deliberately treated
 * as applying + errored (surfaced with no credit) - never as a silent skip that would inflate scores
 */
final class Applicability
{
    private function __construct(
        public readonly bool $applies,
        public readonly bool $errored,
        public readonly ?string $error,
    ) {
    }

    public static function applies(): self
    {
        return new self(true, false, null);
    }

    public static function skipped(): self
    {
        return new self(false, false, null);
    }

    public static function errored(string $error): self
    {
        return new self(true, true, $error);
    }
}

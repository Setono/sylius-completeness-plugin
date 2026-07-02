<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Preview;

/**
 * The outcome of evaluating an ad-hoc expression in the preview scratchpad
 */
final class ScratchpadResult
{
    public function __construct(
        public readonly bool $errored,
        public readonly ?string $error,
        public readonly mixed $rawValue,
        /** The interpreted completeness score, or null when the result is not a boolean or number */
        public readonly ?float $score,
    ) {
    }
}

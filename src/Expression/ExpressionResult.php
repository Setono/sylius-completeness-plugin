<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression;

use Setono\SyliusCompletenessPlugin\Exception\UnexpectedExpressionResultException;

/**
 * Interprets the result of an evaluated expression as a completeness score. Shared by the
 * expression checker and the preview scratchpad so both interpret results identically
 */
final class ExpressionResult
{
    private function __construct()
    {
    }

    /**
     * A boolean maps to 1.0/0.0, a number is used as the fraction directly (unclamped — the
     * calculator clamps to [0, 1]) and any other type throws
     */
    public static function toScore(mixed $result): float
    {
        if (is_bool($result)) {
            return $result ? 1.0 : 0.0;
        }

        if (is_int($result) || is_float($result)) {
            return (float) $result;
        }

        throw new UnexpectedExpressionResultException(sprintf(
            'Expected the expression to return a boolean or a number, got %s',
            get_debug_type($result),
        ));
    }
}

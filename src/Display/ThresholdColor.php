<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Display;

/**
 * Resolves the color a ratio should be rendered in, relative to a "ready" threshold:
 * green at or above the threshold, amber within the configurable band below it, red under that.
 * A null ratio (N/A) is its own state
 */
final class ThresholdColor
{
    public const GREEN = 'green';

    public const AMBER = 'amber';

    public const RED = 'red';

    public const NA = 'na';

    private function __construct()
    {
    }

    public static function resolve(?int $ratio, int $threshold, int $amberBand): string
    {
        if (null === $ratio) {
            return self::NA;
        }

        if ($ratio >= $threshold) {
            return self::GREEN;
        }

        if ($amberBand > 0 && $ratio >= $threshold - $amberBand) {
            return self::AMBER;
        }

        return self::RED;
    }
}

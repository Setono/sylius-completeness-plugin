<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Rollup;

/**
 * A context that survived the rollup pre-filter (measured and not excluded)
 */
final class RollupItem
{
    public function __construct(
        public readonly string $channelCode,
        public readonly string $localeCode,
        public readonly int $ratio,
        public readonly float $weight,
    ) {
    }
}

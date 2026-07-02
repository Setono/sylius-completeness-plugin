<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Resolver;

/**
 * Resolves the "ready" threshold for a context: the per context override when set,
 * otherwise the globally configured default. Used by the display layer for color coding
 */
interface ThresholdResolverInterface
{
    public function resolve(string $channelCode, string $localeCode): int;

    /**
     * Returns the global default threshold (used by surfaces showing the single global rollup)
     */
    public function resolveDefault(): int;
}

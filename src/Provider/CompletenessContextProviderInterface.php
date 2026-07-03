<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Provider;

/**
 * Resolves the per (channel, locale) settings. A missing row means defaults: rollup weight 1.0
 * and no per context threshold, so an empty settings table reproduces flat average behavior
 */
interface CompletenessContextProviderInterface
{
    public function getRollupWeight(string $channelCode, string $localeCode): float;

    /**
     * Returns the context's "ready" threshold or null when the globally configured default applies
     */
    public function getThreshold(string $channelCode, string $localeCode): ?int;
}

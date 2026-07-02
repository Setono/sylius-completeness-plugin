<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Resolver;

use Setono\SyliusCompletenessPlugin\Provider\ContextSettingsProviderInterface;

final class ThresholdResolver implements ThresholdResolverInterface
{
    public function __construct(
        private readonly ContextSettingsProviderInterface $contextSettings,
        private readonly int $defaultThreshold,
    ) {
    }

    public function resolve(string $channelCode, string $localeCode): int
    {
        return $this->contextSettings->getThreshold($channelCode, $localeCode) ?? $this->defaultThreshold;
    }

    public function resolveDefault(): int
    {
        return $this->defaultThreshold;
    }
}

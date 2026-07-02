<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Rollup;

/**
 * Uses only the contexts of the configured default channel (setono_sylius_completeness.default_channel_code).
 * Falls back to a weighted average over all contexts when no default channel is configured or the
 * product has no measured contexts in it
 */
final class DefaultChannelRollupStrategy implements RollupStrategyInterface
{
    public function __construct(
        private readonly WeightedAverageRollupStrategy $weightedAverage,
        private readonly ?string $defaultChannelCode,
    ) {
    }

    public static function getName(): string
    {
        return 'default_channel';
    }

    public function rollup(array $items): int
    {
        if (null !== $this->defaultChannelCode) {
            $defaultChannelItems = array_values(array_filter(
                $items,
                fn (RollupItem $item): bool => $item->channelCode === $this->defaultChannelCode,
            ));

            if ([] !== $defaultChannelItems) {
                return $this->weightedAverage->rollup($defaultChannelItems);
            }
        }

        return $this->weightedAverage->rollup($items);
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\ViewModel;

/**
 * The data for the channel × locale breakdown panel rendered on the product show and edit pages
 */
final class CompletenessPanel
{
    /**
     * @param list<string> $channelCodes row order
     * @param list<string> $localeCodes column order
     * @param array<string, CompletenessCell> $cells keyed by "channelCode|localeCode"
     */
    public function __construct(
        public readonly array $channelCodes,
        public readonly array $localeCodes,
        public readonly array $cells,
        public readonly ?int $globalRatio,
        public readonly int $globalThreshold,
        public readonly string $globalColor,
        public readonly bool $stale,
        public readonly ?\DateTimeImmutable $lastCalculatedAt,
    ) {
    }

    public function hasData(): bool
    {
        return [] !== $this->cells;
    }

    public function isSingleContext(): bool
    {
        return 1 === count($this->cells);
    }

    public function getCell(string $channelCode, string $localeCode): ?CompletenessCell
    {
        return $this->cells[$channelCode . '|' . $localeCode] ?? null;
    }

    public function getSingleCell(): ?CompletenessCell
    {
        if (!$this->isSingleContext()) {
            return null;
        }

        return array_values($this->cells)[0];
    }
}

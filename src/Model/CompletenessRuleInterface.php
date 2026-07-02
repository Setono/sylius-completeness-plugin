<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

interface CompletenessRuleInterface extends ResourceInterface
{
    public const WEIGHT_TIER_LOW = 'low';

    public const WEIGHT_TIER_MEDIUM = 'medium';

    public const WEIGHT_TIER_HIGH = 'high';

    public const WEIGHT_TIER_CRITICAL = 'critical';

    public function getId(): ?int;

    public function getCode(): ?string;

    public function setCode(?string $code): void;

    public function getLabel(): ?string;

    public function setLabel(?string $label): void;

    public function getGroup(): ?string;

    public function setGroup(?string $group): void;

    public function getType(): ?string;

    public function setType(?string $type): void;

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array;

    /**
     * @param array<string, mixed> $configuration
     */
    public function setConfiguration(array $configuration): void;

    public function getWeightTier(): string;

    public function setWeightTier(string $weightTier): void;

    public function getCustomWeight(): ?float;

    public function setCustomWeight(?float $customWeight): void;

    public function isEnabled(): bool;

    public function setEnabled(bool $enabled): void;

    public function getChannelCode(): ?string;

    public function setChannelCode(?string $channelCode): void;

    public function getLocaleCode(): ?string;

    public function setLocaleCode(?string $localeCode): void;

    public function getTaxonCode(): ?string;

    public function setTaxonCode(?string $taxonCode): void;

    public function getCondition(): ?string;

    public function setCondition(?string $condition): void;

    public function getExpression(): ?string;

    public function setExpression(?string $expression): void;

    public function getPosition(): int;

    public function setPosition(int $position): void;
}

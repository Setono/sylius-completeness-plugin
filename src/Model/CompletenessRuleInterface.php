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

    /**
     * An empty list means "all channels". Otherwise the rule applies only in the listed channels.
     *
     * @return list<string>
     */
    public function getChannelCodes(): array;

    /**
     * @param list<string> $channelCodes
     */
    public function setChannelCodes(array $channelCodes): void;

    /**
     * An empty list means "all locales". Otherwise the rule applies only for the listed locales.
     *
     * @return list<string>
     */
    public function getLocaleCodes(): array;

    /**
     * @param list<string> $localeCodes
     */
    public function setLocaleCodes(array $localeCodes): void;

    /**
     * An empty list means "all taxons". Otherwise the rule applies only to products in at least one
     * of the listed taxons.
     *
     * @return list<string>
     */
    public function getTaxonCodes(): array;

    /**
     * @param list<string> $taxonCodes
     */
    public function setTaxonCodes(array $taxonCodes): void;

    public function getCondition(): ?string;

    public function setCondition(?string $condition): void;

    public function getPosition(): int;

    public function setPosition(int $position): void;
}

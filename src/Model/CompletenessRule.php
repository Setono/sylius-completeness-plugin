<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Model;

class CompletenessRule implements CompletenessRuleInterface
{
    protected ?int $id = null;

    protected ?string $code = null;

    protected ?string $label = null;

    protected ?string $group = null;

    protected ?string $type = null;

    /** @var array<string, mixed> */
    protected array $configuration = [];

    protected string $weightTier = self::WEIGHT_TIER_MEDIUM;

    protected ?float $customWeight = null;

    protected bool $enabled = true;

    /** @var list<string> */
    protected array $channelCodes = [];

    /** @var list<string> */
    protected array $localeCodes = [];

    /** @var list<string> */
    protected array $taxonCodes = [];

    protected ?string $condition = null;

    protected int $position = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function setGroup(?string $group): void
    {
        $this->group = $group;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function getWeightTier(): string
    {
        return $this->weightTier;
    }

    public function setWeightTier(string $weightTier): void
    {
        $this->weightTier = $weightTier;
    }

    public function getCustomWeight(): ?float
    {
        return $this->customWeight;
    }

    public function setCustomWeight(?float $customWeight): void
    {
        $this->customWeight = $customWeight;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getChannelCodes(): array
    {
        return $this->channelCodes;
    }

    public function setChannelCodes(array $channelCodes): void
    {
        $this->channelCodes = array_values($channelCodes);
    }

    public function getLocaleCodes(): array
    {
        return $this->localeCodes;
    }

    public function setLocaleCodes(array $localeCodes): void
    {
        $this->localeCodes = array_values($localeCodes);
    }

    public function getTaxonCodes(): array
    {
        return $this->taxonCodes;
    }

    public function setTaxonCodes(array $taxonCodes): void
    {
        $this->taxonCodes = array_values($taxonCodes);
    }

    public function getCondition(): ?string
    {
        return $this->condition;
    }

    public function setCondition(?string $condition): void
    {
        $this->condition = $condition;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }
}

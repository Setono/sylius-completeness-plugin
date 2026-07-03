<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Model;

class CompletenessContext implements CompletenessContextInterface
{
    protected ?int $id = null;

    protected ?string $channelCode = null;

    protected ?string $localeCode = null;

    protected ?int $threshold = null;

    protected float $rollupWeight = 1.0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChannelCode(): ?string
    {
        return $this->channelCode;
    }

    public function setChannelCode(?string $channelCode): void
    {
        $this->channelCode = $channelCode;
    }

    public function getLocaleCode(): ?string
    {
        return $this->localeCode;
    }

    public function setLocaleCode(?string $localeCode): void
    {
        $this->localeCode = $localeCode;
    }

    public function getThreshold(): ?int
    {
        return $this->threshold;
    }

    public function setThreshold(?int $threshold): void
    {
        $this->threshold = $threshold;
    }

    public function getRollupWeight(): float
    {
        return $this->rollupWeight;
    }

    public function setRollupWeight(float $rollupWeight): void
    {
        $this->rollupWeight = $rollupWeight;
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

interface CompletenessContextInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getChannelCode(): ?string;

    public function setChannelCode(?string $channelCode): void;

    public function getLocaleCode(): ?string;

    public function setLocaleCode(?string $localeCode): void;

    /**
     * Returns the "ready" threshold (0-100) for this context or null if the globally configured default applies
     */
    public function getThreshold(): ?int;

    public function setThreshold(?int $threshold): void;

    /**
     * Returns the weight of this context in the global rollup. 0 means the context is excluded from the rollup
     */
    public function getRollupWeight(): float;

    public function setRollupWeight(float $rollupWeight): void;
}

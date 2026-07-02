<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Model;

use Sylius\Component\Core\Model\ProductInterface;

class ProductCompleteness implements ProductCompletenessInterface
{
    protected ?int $id = null;

    protected ?ProductInterface $product = null;

    protected ?string $channelCode = null;

    protected ?string $localeCode = null;

    protected ?int $ratio = null;

    protected float $weightedPassed = 0.0;

    protected float $weightedTotal = 0.0;

    /** @var list<array{group: ?string, ratio: ?int, weightedPassed: float, weightedTotal: float}> */
    protected array $groupScores = [];

    /** @var list<array{code: string, label: string, group: ?string, checkerType: string, weight: float, score: float, errored: bool, error?: ?string}> */
    protected array $unmetRules = [];

    protected ?\DateTimeImmutable $calculatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?ProductInterface
    {
        return $this->product;
    }

    public function setProduct(?ProductInterface $product): void
    {
        $this->product = $product;
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

    public function getRatio(): ?int
    {
        return $this->ratio;
    }

    public function setRatio(?int $ratio): void
    {
        $this->ratio = $ratio;
    }

    public function getWeightedPassed(): float
    {
        return $this->weightedPassed;
    }

    public function setWeightedPassed(float $weightedPassed): void
    {
        $this->weightedPassed = $weightedPassed;
    }

    public function getWeightedTotal(): float
    {
        return $this->weightedTotal;
    }

    public function setWeightedTotal(float $weightedTotal): void
    {
        $this->weightedTotal = $weightedTotal;
    }

    public function getGroupScores(): array
    {
        return $this->groupScores;
    }

    public function setGroupScores(array $groupScores): void
    {
        $this->groupScores = $groupScores;
    }

    public function getUnmetRules(): array
    {
        return $this->unmetRules;
    }

    public function setUnmetRules(array $unmetRules): void
    {
        $this->unmetRules = $unmetRules;
    }

    public function getCalculatedAt(): ?\DateTimeImmutable
    {
        return $this->calculatedAt;
    }

    public function setCalculatedAt(?\DateTimeImmutable $calculatedAt): void
    {
        $this->calculatedAt = $calculatedAt;
    }
}

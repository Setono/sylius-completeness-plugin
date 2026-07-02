<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Model;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface ProductCompletenessInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getProduct(): ?ProductInterface;

    public function setProduct(?ProductInterface $product): void;

    public function getChannelCode(): ?string;

    public function setChannelCode(?string $channelCode): void;

    public function getLocaleCode(): ?string;

    public function setLocaleCode(?string $localeCode): void;

    /**
     * Returns the completeness ratio (0-100) for this (channel, locale) context or null if no rules applied (N/A)
     */
    public function getRatio(): ?int;

    public function setRatio(?int $ratio): void;

    public function getWeightedPassed(): float;

    public function setWeightedPassed(float $weightedPassed): void;

    public function getWeightedTotal(): float;

    public function setWeightedTotal(float $weightedTotal): void;

    /**
     * @return list<array{group: ?string, ratio: ?int, weightedPassed: float, weightedTotal: float}>
     */
    public function getGroupScores(): array;

    /**
     * @param list<array{group: ?string, ratio: ?int, weightedPassed: float, weightedTotal: float}> $groupScores
     */
    public function setGroupScores(array $groupScores): void;

    /**
     * @return list<array{code: string, label: string, group: ?string, checkerType: string, weight: float, score: float, errored: bool, error?: ?string}>
     */
    public function getUnmetRules(): array;

    /**
     * @param list<array{code: string, label: string, group: ?string, checkerType: string, weight: float, score: float, errored: bool, error?: ?string}> $unmetRules
     */
    public function setUnmetRules(array $unmetRules): void;

    public function getCalculatedAt(): ?\DateTimeImmutable;

    public function setCalculatedAt(?\DateTimeImmutable $calculatedAt): void;
}

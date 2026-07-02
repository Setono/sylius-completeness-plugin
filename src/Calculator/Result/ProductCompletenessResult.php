<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Calculator\Result;

/**
 * The complete outcome of calculating a product's completeness across all its contexts.
 * This is the stable, documented return value of the public calculator API
 */
final class ProductCompletenessResult
{
    public function __construct(
        /** Null when every context is N/A or excluded from the rollup */
        public readonly ?int $globalRatio,
        /** @var list<ContextResult> */
        public readonly array $contextResults,
        /** The rubric version the product was scored against */
        public readonly int $rubricVersion,
        public readonly \DateTimeImmutable $calculatedAt,
    ) {
    }

    public function getContextResult(string $channelCode, string $localeCode): ?ContextResult
    {
        foreach ($this->contextResults as $contextResult) {
            if ($contextResult->channelCode === $channelCode && $contextResult->localeCode === $localeCode) {
                return $contextResult;
            }
        }

        return null;
    }
}

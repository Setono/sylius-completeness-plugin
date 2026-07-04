<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Provider;

interface ProductIdsProviderInterface
{
    /**
     * Yields chunks of product ids ordered by id. When $codes is given, only products
     * with those codes are included
     *
     * @param positive-int $chunkSize
     * @param list<string>|null $codes
     *
     * @return iterable<list<int>>
     */
    public function getChunks(int $chunkSize = 100, ?array $codes = null): iterable;

    /**
     * Yields chunks of ids of the products that need recalculating: the ones explicitly marked dirty
     * (completenessDirtyAt) plus the ones stale against the given rubric version (never scored, or
     * scored against an older version)
     *
     * @param positive-int $chunkSize
     *
     * @return iterable<list<int>>
     */
    public function getRecalculationCandidateChunks(int $chunkSize, int $currentVersion): iterable;
}

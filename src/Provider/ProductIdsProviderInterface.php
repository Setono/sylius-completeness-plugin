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
}

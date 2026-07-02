<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Provider;

use Sylius\Component\Core\Model\ProductInterface;

interface ProductProviderInterface
{
    public function findById(int $id): ?ProductInterface;

    /**
     * @param list<int> $ids
     *
     * @return list<ProductInterface>
     */
    public function findByIds(array $ids): array;
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final class ProductIdsProvider implements ProductIdsProviderInterface
{
    /**
     * @param class-string $productClass
     */
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly string $productClass,
    ) {
    }

    public function getChunks(int $chunkSize = 100, ?array $codes = null): iterable
    {
        $manager = $this->managerRegistry->getManagerForClass($this->productClass);
        if (!$manager instanceof EntityManagerInterface) {
            throw new \RuntimeException(sprintf('No entity manager found for class %s', $this->productClass));
        }

        $lastId = 0;

        while (true) {
            $queryBuilder = $manager->createQueryBuilder()
                ->select('o.id')
                ->from($this->productClass, 'o')
                ->andWhere('o.id > :lastId')
                ->setParameter('lastId', $lastId)
                ->orderBy('o.id', 'ASC')
                ->setMaxResults($chunkSize);

            if (null !== $codes) {
                $queryBuilder
                    ->andWhere('o.code IN (:codes)')
                    ->setParameter('codes', $codes);
            }

            /** @var list<array{id: int|string}> $rows */
            $rows = $queryBuilder->getQuery()->getArrayResult();

            $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);
            if ([] === $ids) {
                return;
            }

            yield $ids;

            $lastId = $ids[count($ids) - 1];
        }
    }
}

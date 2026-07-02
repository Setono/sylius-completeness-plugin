<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class ProductCompletenessRepository extends EntityRepository implements ProductCompletenessRepositoryInterface
{
    public function findRatiosGroupedByProduct(array $productIds): array
    {
        if ([] === $productIds) {
            return [];
        }

        /** @var list<array{productId: int|string, channelCode: string, localeCode: string, ratio: int|string|null}> $rows */
        $rows = $this->createQueryBuilder('o')
            ->select('IDENTITY(o.product) AS productId, o.channelCode AS channelCode, o.localeCode AS localeCode, o.ratio AS ratio')
            ->andWhere('o.product IN (:productIds)')
            ->setParameter('productIds', $productIds)
            ->getQuery()
            ->getArrayResult();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['productId']][] = [
                'channelCode' => $row['channelCode'],
                'localeCode' => $row['localeCode'],
                'ratio' => null === $row['ratio'] ? null : (int) $row['ratio'],
            ];
        }

        return $grouped;
    }
}

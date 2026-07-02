<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Core\Model\ProductInterface;

final class ProductProvider implements ProductProviderInterface
{
    /**
     * @param class-string $productClass
     */
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly string $productClass,
    ) {
    }

    public function findById(int $id): ?ProductInterface
    {
        $product = $this->getManager()->find($this->productClass, $id);

        return $product instanceof ProductInterface ? $product : null;
    }

    public function findByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        /** @var list<object> $products */
        $products = $this->getManager()->createQueryBuilder()
            ->select('o')
            ->from($this->productClass, 'o')
            ->andWhere('o.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('o.id', 'ASC')
            ->getQuery()
            ->getResult();

        return array_values(array_filter(
            $products,
            static fn (object $product): bool => $product instanceof ProductInterface,
        ));
    }

    private function getManager(): EntityManagerInterface
    {
        $manager = $this->managerRegistry->getManagerForClass($this->productClass);
        if (!$manager instanceof EntityManagerInterface) {
            throw new \RuntimeException(sprintf('No entity manager found for class %s', $this->productClass));
        }

        return $manager;
    }
}

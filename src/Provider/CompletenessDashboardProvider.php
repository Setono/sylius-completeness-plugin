<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManagerInterface;
use Setono\SyliusCompletenessPlugin\ViewModel\DashboardStatistics;
use Sylius\Component\Core\Model\ProductInterface;

/**
 * Computes catalog-wide completeness figures from the host product table (which the plugin's trait
 * has extended with the rolled-up completenessRatio and completenessRubricVersion).
 */
final class CompletenessDashboardProvider implements CompletenessDashboardProviderInterface
{
    private const BANDS = [[0, 19], [20, 39], [40, 59], [60, 79], [80, 100]];

    /**
     * @param class-string<ProductInterface> $productClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RubricVersionManagerInterface $rubricVersionManager,
        private readonly string $productClass,
        private readonly int $readyThreshold,
    ) {
    }

    public function getStatistics(): DashboardStatistics
    {
        $total = $this->count('');
        $scored = $this->count('WHERE p.completenessRatio IS NOT NULL');
        $ready = $this->count('WHERE p.completenessRatio >= :threshold', ['threshold' => $this->readyThreshold]);
        $stale = $this->count(
            'WHERE p.completenessRubricVersion IS NOT NULL AND p.completenessRubricVersion < :version',
            ['version' => $this->rubricVersionManager->getCurrentVersion()],
        );

        $average = 0;
        if ($scored > 0) {
            $raw = $this->entityManager
                ->createQuery(sprintf('SELECT AVG(p.completenessRatio) FROM %s p WHERE p.completenessRatio IS NOT NULL', $this->productClass))
                ->getSingleScalarResult();
            $average = (int) round((float) $raw);
        }

        $distribution = [];
        foreach (self::BANDS as [$from, $to]) {
            $distribution[] = [
                'from' => $from,
                'to' => $to,
                'count' => $this->count('WHERE p.completenessRatio BETWEEN :from AND :to', ['from' => $from, 'to' => $to]),
            ];
        }

        return new DashboardStatistics($total, $scored, $average, $ready, $stale, $this->readyThreshold, $distribution);
    }

    public function getLowestScoringProducts(int $limit): array
    {
        /** @var list<ProductInterface> $products */
        $products = $this->entityManager
            ->createQuery(sprintf(
                'SELECT p FROM %s p WHERE p.completenessRatio IS NOT NULL ORDER BY p.completenessRatio ASC, p.id ASC',
                $this->productClass,
            ))
            ->setMaxResults($limit)
            ->getResult();

        return $products;
    }

    /**
     * @param array<string, int> $parameters
     */
    private function count(string $where, array $parameters = []): int
    {
        $query = $this->entityManager->createQuery(trim(sprintf('SELECT COUNT(p.id) FROM %s p %s', $this->productClass, $where)));
        foreach ($parameters as $name => $value) {
            $query->setParameter($name, $value);
        }

        return (int) $query->getSingleScalarResult();
    }
}

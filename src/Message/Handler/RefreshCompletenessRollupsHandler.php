<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Message\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\SyliusCompletenessPlugin\Calculator\Result\ContextResult;
use Setono\SyliusCompletenessPlugin\Message\Command\RefreshCompletenessRollups;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessAwareInterface;
use Setono\SyliusCompletenessPlugin\Provider\ContextSettingsProviderInterface;
use Setono\SyliusCompletenessPlugin\Provider\ProductIdsProviderInterface;
use Setono\SyliusCompletenessPlugin\Provider\ProductProviderInterface;
use Setono\SyliusCompletenessPlugin\Repository\ProductCompletenessRepositoryInterface;
use Setono\SyliusCompletenessPlugin\Rollup\RollupInterface;
use Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManagerInterface;

/**
 * Recomputes global ratios from the EXISTING per context rows without re-evaluating any rules.
 * Notice that this is not a calculation, so no ProductCompletenessCalculated event is dispatched
 */
final class RefreshCompletenessRollupsHandler
{
    private const CHUNK_SIZE = 100;

    /**
     * @param class-string $productClass
     */
    public function __construct(
        private readonly ProductIdsProviderInterface $productIdsProvider,
        private readonly ProductProviderInterface $productProvider,
        private readonly ProductCompletenessRepositoryInterface $completenessRepository,
        private readonly ContextSettingsProviderInterface $contextSettings,
        private readonly RollupInterface $rollup,
        private readonly RubricVersionManagerInterface $rubricVersionManager,
        private readonly ManagerRegistry $managerRegistry,
        private readonly string $productClass,
    ) {
    }

    public function __invoke(RefreshCompletenessRollups $message): void
    {
        $rubricVersion = $this->rubricVersionManager->getCurrentVersion();

        foreach ($this->productIdsProvider->getChunks(self::CHUNK_SIZE) as $ids) {
            $ratiosByProduct = $this->completenessRepository->findRatiosGroupedByProduct($ids);

            $manager = $this->managerRegistry->getManagerForClass($this->productClass);
            if (null === $manager) {
                throw new \RuntimeException(sprintf('No object manager found for class %s', $this->productClass));
            }

            foreach ($this->productProvider->findByIds($ids) as $product) {
                if (!$product instanceof ProductCompletenessAwareInterface) {
                    continue;
                }

                $productId = $product->getId();

                $contextResults = [];
                foreach ($ratiosByProduct[$productId] ?? [] as ['channelCode' => $channelCode, 'localeCode' => $localeCode, 'ratio' => $ratio]) {
                    $rollupWeight = $this->contextSettings->getRollupWeight($channelCode, $localeCode);

                    $contextResults[] = new ContextResult(
                        channelCode: $channelCode,
                        localeCode: $localeCode,
                        ratio: $ratio,
                        weightedPassed: 0.0,
                        weightedTotal: 0.0,
                        groupScores: [],
                        ruleResults: [],
                        rollupWeight: $rollupWeight,
                        excluded: 0.0 === $rollupWeight,
                    );
                }

                $product->setCompletenessRatio($this->rollup->rollup($contextResults));
                $product->setCompletenessRubricVersion($rubricVersion);
            }

            $manager->flush();
            $manager->clear();
        }
    }
}

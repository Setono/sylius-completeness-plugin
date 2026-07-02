<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Updater;

use Doctrine\Persistence\ManagerRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\SyliusCompletenessPlugin\Calculator\CompletenessCalculatorInterface;
use Setono\SyliusCompletenessPlugin\Calculator\Result\ContextResult;
use Setono\SyliusCompletenessPlugin\Calculator\Result\ProductCompletenessResult;
use Setono\SyliusCompletenessPlugin\Event\ProductCompletenessCalculated;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessAwareInterface;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class ProductCompletenessUpdater implements ProductCompletenessUpdaterInterface
{
    /**
     * @param FactoryInterface<ProductCompletenessInterface> $completenessFactory
     */
    public function __construct(
        private readonly CompletenessCalculatorInterface $calculator,
        private readonly FactoryInterface $completenessFactory,
        private readonly ManagerRegistry $managerRegistry,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function update(ProductInterface $product, bool $bulk = false): ProductCompletenessResult
    {
        if (!$product instanceof ProductCompletenessAwareInterface) {
            throw new \InvalidArgumentException(sprintf(
                'The product %s does not implement %s. Apply the ProductCompletenessAwareTrait and interface to your Product entity as described in the plugin README',
                $product::class,
                ProductCompletenessAwareInterface::class,
            ));
        }

        $result = $this->calculator->calculate($product);

        /** @var array<string, ProductCompletenessInterface> $existing */
        $existing = [];
        foreach ($product->getCompletenesses() as $row) {
            $existing[$row->getChannelCode() . '|' . $row->getLocaleCode()] = $row;
        }

        $seen = [];
        foreach ($result->contextResults as $contextResult) {
            $key = $contextResult->channelCode . '|' . $contextResult->localeCode;
            $seen[$key] = true;

            $row = $existing[$key] ?? null;
            if (null === $row) {
                $row = $this->createRow($contextResult);
                $product->addCompleteness($row);
            }

            $row->setRatio($contextResult->ratio);
            $row->setWeightedPassed($contextResult->weightedPassed);
            $row->setWeightedTotal($contextResult->weightedTotal);
            $row->setGroupScores(self::serializeGroupScores($contextResult));
            $row->setUnmetRules(self::serializeUnmetRules($contextResult));
            $row->setCalculatedAt($result->calculatedAt);
        }

        // prune rows for contexts that no longer exist (orphan removal deletes them)
        foreach ($existing as $key => $row) {
            if (!isset($seen[$key])) {
                $product->removeCompleteness($row);
            }
        }

        $product->setCompletenessRatio($result->globalRatio);
        $product->setCompletenessRubricVersion($result->rubricVersion);

        $manager = $this->managerRegistry->getManagerForClass($product::class);
        if (null === $manager) {
            throw new \RuntimeException(sprintf('No object manager found for class %s', $product::class));
        }
        $manager->flush();

        $this->eventDispatcher->dispatch(new ProductCompletenessCalculated($product, $result, $bulk));

        return $result;
    }

    private function createRow(ContextResult $contextResult): ProductCompletenessInterface
    {
        $row = $this->completenessFactory->createNew();
        if (!$row instanceof ProductCompletenessInterface) {
            throw new \RuntimeException(sprintf(
                'The product completeness factory must create instances of %s',
                ProductCompletenessInterface::class,
            ));
        }

        $row->setChannelCode($contextResult->channelCode);
        $row->setLocaleCode($contextResult->localeCode);

        return $row;
    }

    /**
     * @return list<array{group: ?string, ratio: ?int, weightedPassed: float, weightedTotal: float}>
     */
    private static function serializeGroupScores(ContextResult $contextResult): array
    {
        $groupScores = [];
        foreach ($contextResult->groupScores as $groupScore) {
            $groupScores[] = [
                'group' => $groupScore->group,
                'ratio' => $groupScore->ratio,
                'weightedPassed' => $groupScore->weightedPassed,
                'weightedTotal' => $groupScore->weightedTotal,
            ];
        }

        return $groupScores;
    }

    /**
     * @return list<array{code: string, label: string, group: ?string, checkerType: string, weight: float, score: float, errored: bool, error?: ?string}>
     */
    private static function serializeUnmetRules(ContextResult $contextResult): array
    {
        $unmetRules = [];
        foreach ($contextResult->getUnmetRuleResults() as $ruleResult) {
            $unmetRule = [
                'code' => $ruleResult->code,
                'label' => $ruleResult->label,
                'group' => $ruleResult->group,
                'checkerType' => $ruleResult->checkerType,
                'weight' => $ruleResult->weight,
                'score' => $ruleResult->score,
                'errored' => $ruleResult->errored,
            ];

            if (null !== $ruleResult->error) {
                $unmetRule['error'] = $ruleResult->error;
            }

            $unmetRules[] = $unmetRule;
        }

        return $unmetRules;
    }
}

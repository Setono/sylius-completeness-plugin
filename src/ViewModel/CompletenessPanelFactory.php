<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\ViewModel;

use Setono\SyliusCompletenessPlugin\Display\ThresholdColor;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessAwareInterface;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessInterface;
use Setono\SyliusCompletenessPlugin\Provider\ContextSettingsProviderInterface;
use Setono\SyliusCompletenessPlugin\Resolver\ThresholdResolverInterface;
use Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManagerInterface;
use Sylius\Component\Core\Model\ProductInterface;

/**
 * Assembles the breakdown panel from a product's already loaded completeness rows. All lookups
 * (thresholds, rollup weights, the current rubric version) are memoized by the collaborators, so
 * building the panel touches the database at most once per request
 */
final class CompletenessPanelFactory implements CompletenessPanelFactoryInterface
{
    public function __construct(
        private readonly ThresholdResolverInterface $thresholdResolver,
        private readonly ContextSettingsProviderInterface $contextSettings,
        private readonly RubricVersionManagerInterface $rubricVersionManager,
        private readonly int $amberBand,
    ) {
    }

    public function create(ProductInterface $product): CompletenessPanel
    {
        $rows = $product instanceof ProductCompletenessAwareInterface ? $product->getCompletenesses() : [];

        /** @var array<string, true> $channelCodes */
        $channelCodes = [];
        /** @var array<string, true> $localeCodes */
        $localeCodes = [];
        $cells = [];
        $lastCalculatedAt = null;

        foreach ($rows as $row) {
            if (!$row instanceof ProductCompletenessInterface) {
                continue;
            }

            $channelCode = (string) $row->getChannelCode();
            $localeCode = (string) $row->getLocaleCode();

            $channelCodes[$channelCode] = true;
            $localeCodes[$localeCode] = true;

            $threshold = $this->thresholdResolver->resolve($channelCode, $localeCode);
            $rollupWeight = $this->contextSettings->getRollupWeight($channelCode, $localeCode);

            $calculatedAt = $row->getCalculatedAt();
            if (null !== $calculatedAt && ($lastCalculatedAt === null || $calculatedAt > $lastCalculatedAt)) {
                $lastCalculatedAt = $calculatedAt;
            }

            $cells[$channelCode . '|' . $localeCode] = new CompletenessCell(
                channelCode: $channelCode,
                localeCode: $localeCode,
                ratio: $row->getRatio(),
                threshold: $threshold,
                color: ThresholdColor::resolve($row->getRatio(), $threshold, $this->amberBand),
                excluded: 0.0 === $rollupWeight,
                groupScores: $row->getGroupScores(),
                unmetRuleGroups: self::groupUnmetRules($row->getUnmetRules()),
                calculatedAt: $calculatedAt,
            );
        }

        $globalRatio = $product instanceof ProductCompletenessAwareInterface ? $product->getCompletenessRatio() : null;
        $globalThreshold = $this->thresholdResolver->resolveDefault();

        return new CompletenessPanel(
            channelCodes: array_keys($channelCodes),
            localeCodes: array_keys($localeCodes),
            cells: $cells,
            globalRatio: $globalRatio,
            globalThreshold: $globalThreshold,
            globalColor: ThresholdColor::resolve($globalRatio, $globalThreshold, $this->amberBand),
            stale: $this->isStale($product),
            lastCalculatedAt: $lastCalculatedAt,
        );
    }

    private function isStale(ProductInterface $product): bool
    {
        if (!$product instanceof ProductCompletenessAwareInterface) {
            return false;
        }

        $stampedVersion = $product->getCompletenessRubricVersion();

        return null !== $stampedVersion && $stampedVersion < $this->rubricVersionManager->getCurrentVersion();
    }

    /**
     * Groups unmet rules by group and orders each group's rules by resolved weight descending,
     * so the biggest score gains surface first
     *
     * @param list<array{code: string, label: string, group: ?string, checkerType: string, weight: float, score: float, errored: bool, error?: ?string}> $unmetRules
     *
     * @return list<array{group: ?string, rules: list<array{code: string, label: string, group: ?string, checkerType: string, weight: float, score: float, errored: bool, error?: ?string}>}>
     */
    private static function groupUnmetRules(array $unmetRules): array
    {
        /** @var array<string, array{group: ?string, rules: list<array{code: string, label: string, group: ?string, checkerType: string, weight: float, score: float, errored: bool, error?: ?string}>}> $groups */
        $groups = [];
        foreach ($unmetRules as $rule) {
            $key = $rule['group'] ?? "\0ungrouped";
            $groups[$key] ??= ['group' => $rule['group'], 'rules' => []];
            $groups[$key]['rules'][] = $rule;
        }

        $result = [];
        foreach ($groups as $group) {
            usort($group['rules'], static fn (array $a, array $b): int => $b['weight'] <=> $a['weight']);
            $result[] = $group;
        }

        return $result;
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Twig;

use Setono\SyliusCompletenessPlugin\Calculator\RuleWeightResolverInterface;
use Setono\SyliusCompletenessPlugin\Display\ThresholdColor;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionFunctionNameProviderInterface;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRuleInterface;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessAwareInterface;
use Setono\SyliusCompletenessPlugin\Repository\CompletenessRuleRepositoryInterface;
use Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManagerInterface;
use Setono\SyliusCompletenessPlugin\ViewModel\CompletenessPanel;
use Setono\SyliusCompletenessPlugin\ViewModel\CompletenessPanelFactoryInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class CompletenessDisplayRuntime implements RuntimeExtensionInterface, ResetInterface
{
    private ?float $totalWeight = null;

    private ?int $currentRubricVersion = null;

    /**
     * @param array<string, string> $checkers checker type => label
     */
    public function __construct(
        private readonly CompletenessRuleRepositoryInterface $ruleRepository,
        private readonly RuleWeightResolverInterface $weightResolver,
        private readonly RubricVersionManagerInterface $rubricVersionManager,
        private readonly CompletenessPanelFactoryInterface $panelFactory,
        private readonly ExpressionFunctionNameProviderInterface $functionNameProvider,
        private readonly array $checkers,
        private readonly int $defaultThreshold,
        private readonly int $amberBand,
    ) {
    }

    /**
     * The registered expression function names, used to seed the expression editor autocompletion.
     *
     * @return list<string>
     */
    public function expressionFunctions(): array
    {
        return $this->functionNameProvider->getNames();
    }

    /**
     * Returns this rule's share (0.0-1.0) of the total resolved weight of all enabled rules.
     * Notice that this is a legibility aid computed against the whole enabled set - a scoped
     * rule's real share within the contexts it applies to will differ
     */
    public function ruleShare(CompletenessRuleInterface $rule): float
    {
        if (!$rule->isEnabled()) {
            return 0.0;
        }

        $totalWeight = $this->totalWeight ??= $this->calculateTotalWeight();
        if ($totalWeight <= 0.0) {
            return 0.0;
        }

        return $this->resolveWeightSafely($rule) / $totalWeight;
    }

    /**
     * Returns a resolved weight's share (0.0-1.0) of the total resolved weight of all enabled rules.
     * Used by the preview, which works with resolved weights rather than rule entities
     */
    public function weightShare(float $weight): float
    {
        $totalWeight = $this->totalWeight ??= $this->calculateTotalWeight();

        return $totalWeight <= 0.0 ? 0.0 : $weight / $totalWeight;
    }

    public function checkerLabel(string $type): string
    {
        return $this->checkers[$type] ?? $type;
    }

    /**
     * Returns the color a ratio should render in. A null threshold falls back to the global
     * default (used by the grid column, which shows the single global rollup)
     */
    public function thresholdColor(?int $ratio, ?int $threshold = null): string
    {
        return ThresholdColor::resolve($ratio, $threshold ?? $this->defaultThreshold, $this->amberBand);
    }

    /**
     * Whether the product's stored score is behind the current rubric version and is thus being
     * recalculated in the background
     */
    public function isStale(ProductInterface $product): bool
    {
        if (!$product instanceof ProductCompletenessAwareInterface) {
            return false;
        }

        $stampedVersion = $product->getCompletenessRubricVersion();
        if (null === $stampedVersion) {
            return false;
        }

        $this->currentRubricVersion ??= $this->rubricVersionManager->getCurrentVersion();

        return $stampedVersion < $this->currentRubricVersion;
    }

    public function panel(ProductInterface $product): CompletenessPanel
    {
        return $this->panelFactory->create($product);
    }

    public function reset(): void
    {
        $this->totalWeight = null;
        $this->currentRubricVersion = null;
    }

    private function calculateTotalWeight(): float
    {
        $totalWeight = 0.0;
        foreach ($this->ruleRepository->findEnabled() as $rule) {
            $totalWeight += $this->resolveWeightSafely($rule);
        }

        return $totalWeight;
    }

    private function resolveWeightSafely(CompletenessRuleInterface $rule): float
    {
        try {
            return $this->weightResolver->resolve($rule);
        } catch (\Throwable) {
            return 0.0;
        }
    }
}

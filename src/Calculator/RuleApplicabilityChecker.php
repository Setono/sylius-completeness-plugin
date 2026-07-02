<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Calculator;

use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionEvaluatorInterface;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRuleInterface;
use Setono\SyliusCompletenessPlugin\Util\Taxons;
use Sylius\Component\Core\Model\ProductInterface;

final class RuleApplicabilityChecker implements RuleApplicabilityCheckerInterface
{
    public function __construct(private readonly ExpressionEvaluatorInterface $expressionEvaluator)
    {
    }

    public function check(CompletenessRuleInterface $rule, ProductInterface $product, CompletenessCheckContext $context): Applicability
    {
        if (!$rule->isEnabled()) {
            return Applicability::skipped();
        }

        $channelCode = $rule->getChannelCode();
        if (null !== $channelCode && $channelCode !== $context->getChannelCode()) {
            return Applicability::skipped();
        }

        $localeCode = $rule->getLocaleCode();
        if (null !== $localeCode && $localeCode !== $context->getLocaleCode()) {
            return Applicability::skipped();
        }

        $taxonCode = $rule->getTaxonCode();
        if (null !== $taxonCode && !in_array($taxonCode, Taxons::codes($product), true)) {
            return Applicability::skipped();
        }

        $condition = $rule->getCondition();
        if (null === $condition || '' === trim($condition)) {
            return Applicability::applies();
        }

        try {
            $result = $this->expressionEvaluator->evaluate($condition, $product, $context);
        } catch (\Throwable $e) {
            return Applicability::errored(sprintf('The condition threw an error: %s', $e->getMessage()));
        }

        if (!is_bool($result)) {
            return Applicability::errored(sprintf('The condition must evaluate to a boolean, got %s', get_debug_type($result)));
        }

        return $result ? Applicability::applies() : Applicability::skipped();
    }
}

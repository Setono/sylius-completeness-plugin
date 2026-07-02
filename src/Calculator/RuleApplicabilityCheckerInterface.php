<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Calculator;

use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRuleInterface;
use Sylius\Component\Core\Model\ProductInterface;

interface RuleApplicabilityCheckerInterface
{
    /**
     * Gates a rule against a (product, channel, locale): the rule applies only if it is enabled,
     * every set scope (channel/locale/taxon) matches AND the condition (if any) evaluates to true
     */
    public function check(CompletenessRuleInterface $rule, ProductInterface $product, CompletenessCheckContext $context): Applicability;
}

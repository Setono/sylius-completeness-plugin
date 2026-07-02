<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression;

use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Sylius\Component\Core\Model\ProductInterface;

interface ExpressionEvaluatorInterface
{
    /**
     * Evaluates an expression with the standard variables in scope:
     * product, channel, locale, channelCode, localeCode
     */
    public function evaluate(string $expression, ProductInterface $product, CompletenessCheckContext $context): mixed;
}

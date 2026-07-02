<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Checker;

use Setono\SyliusCompletenessPlugin\Exception\InvalidCheckerConfigurationException;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionEvaluatorInterface;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionResult;
use Sylius\Component\Core\Model\ProductInterface;

/**
 * Evaluates the rule's ExpressionLanguage expression. The calculator copies the rule's expression
 * column into $configuration['expression'] before invoking this checker, so the checker interface
 * stays uniform. A boolean result maps to 1.0/0.0, a numeric result is used as the fraction directly
 * (the calculator clamps it to [0, 1]) and any other result type errors the rule
 */
final class ExpressionChecker implements CompletenessCheckerInterface
{
    public const TYPE = 'expression';

    public function __construct(private readonly ExpressionEvaluatorInterface $expressionEvaluator)
    {
    }

    public static function getType(): string
    {
        return self::TYPE;
    }

    public function score(ProductInterface $product, CompletenessCheckContext $context, array $configuration): float
    {
        $expression = $configuration['expression'] ?? null;
        if (!is_string($expression) || '' === trim($expression)) {
            throw new InvalidCheckerConfigurationException('The expression checker expects a non empty "expression" configuration value');
        }

        return ExpressionResult::toScore($this->expressionEvaluator->evaluate($expression, $product, $context));
    }
}

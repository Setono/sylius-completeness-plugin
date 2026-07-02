<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Preview;

use Setono\SyliusCompletenessPlugin\Calculator\ContextInitializerInterface;
use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionEvaluatorInterface;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionResult;
use Sylius\Component\Core\Model\ProductInterface;

/**
 * Evaluates a one-off expression against a product in a context, exactly as a saved rule would be:
 * same helpers, same bool->1/0 and 0-1-float interpretation, same errored handling. Writes nothing
 */
final class ScratchpadEvaluator implements ScratchpadEvaluatorInterface
{
    public function __construct(
        private readonly ContextInitializerInterface $contextInitializer,
        private readonly ExpressionEvaluatorInterface $expressionEvaluator,
    ) {
    }

    public function evaluate(ProductInterface $product, CompletenessCheckContext $context, string $expression): ScratchpadResult
    {
        try {
            $this->contextInitializer->initialize($product, $context);

            $rawValue = $this->expressionEvaluator->evaluate($expression, $product, $context);

            // a boolean or number yields a score; any other result type has a raw value but no score
            try {
                $score = ExpressionResult::toScore($rawValue);
            } catch (\Throwable) {
                $score = null;
            }

            return new ScratchpadResult(false, null, $rawValue, $score);
        } catch (\Throwable $e) {
            return new ScratchpadResult(true, $e->getMessage(), null, null);
        } finally {
            $this->contextInitializer->terminate();
        }
    }
}

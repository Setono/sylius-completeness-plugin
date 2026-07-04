<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression\FunctionProvider;

use Setono\SyliusCompletenessPlugin\Expression\DocumentedExpressionFunction;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

abstract class FunctionProvider implements ExpressionFunctionProviderInterface
{
    /**
     * Creates an evaluator only function carrying its own documentation. Completeness expressions are
     * always evaluated, never compiled to PHP, so the compiler callback just throws. The signature and
     * description live here, next to the evaluator, so the editor documentation cannot drift
     */
    final protected function createFunction(
        string $name,
        string $signature,
        string $description,
        \Closure $evaluator,
    ): DocumentedExpressionFunction {
        return new DocumentedExpressionFunction(
            $name,
            static function () use ($name): never {
                throw new \LogicException(sprintf('The completeness expression function "%s" cannot be compiled', $name));
            },
            $evaluator,
            $signature,
            $description,
        );
    }

    final protected function assertProduct(mixed $product, string $function): ProductInterface
    {
        if (!$product instanceof ProductInterface) {
            throw new \InvalidArgumentException(sprintf(
                'The %s() function expects a product as its first argument, got %s',
                $function,
                get_debug_type($product),
            ));
        }

        return $product;
    }

    final protected function toNullableString(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        if (is_scalar($value) || $value instanceof \Stringable) {
            $value = (string) $value;

            return '' === $value ? null : $value;
        }

        throw new \InvalidArgumentException(sprintf('Expected a string or null, got %s', get_debug_type($value)));
    }
}

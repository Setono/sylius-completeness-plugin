<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;

/**
 * An expression function that also carries its editor documentation (a signature and a one-line
 * description). Keeping the documentation next to the function definition means it cannot drift
 * out of sync with the actual set of registered functions
 */
final class DocumentedExpressionFunction extends ExpressionFunction
{
    public function __construct(
        string $name,
        callable $compiler,
        callable $evaluator,
        private readonly string $signature,
        private readonly string $description,
    ) {
        parent::__construct($name, $compiler, $evaluator);
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}

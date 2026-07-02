<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression;

use Setono\SyliusCompletenessPlugin\Exception\InvalidExpressionException;

interface ExpressionValidatorInterface
{
    /**
     * Compile validates an expression against the standard variable names
     *
     * @throws InvalidExpressionException if the expression does not compile
     */
    public function validate(string $expression): void;
}

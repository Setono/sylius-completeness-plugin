<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Exception;

use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * Thrown by the expression validator when an expression does not compile
 */
final class InvalidExpressionException extends \InvalidArgumentException
{
    public function __construct(string $expression, SyntaxError $syntaxError)
    {
        parent::__construct(
            sprintf('The expression "%s" is invalid: %s', $expression, $syntaxError->getMessage()),
            0,
            $syntaxError,
        );
    }
}

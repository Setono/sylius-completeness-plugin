<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Exception;

/**
 * Thrown when an expression evaluates to something else than a boolean or a number
 */
final class UnexpectedExpressionResultException extends \RuntimeException
{
}

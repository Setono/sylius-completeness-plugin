<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression;

use Setono\SyliusCompletenessPlugin\Exception\InvalidExpressionException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;

final class ExpressionValidator implements ExpressionValidatorInterface
{
    public const VARIABLE_NAMES = ['product', 'channel', 'locale', 'channelCode', 'localeCode'];

    public function __construct(private readonly ExpressionLanguage $expressionLanguage)
    {
    }

    public function validate(string $expression): void
    {
        try {
            $this->expressionLanguage->lint($expression, self::VARIABLE_NAMES);
        } catch (SyntaxError $e) {
            throw new InvalidExpressionException($expression, $e);
        }
    }
}

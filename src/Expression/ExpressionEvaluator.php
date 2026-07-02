<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression;

use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class ExpressionEvaluator implements ExpressionEvaluatorInterface
{
    public function __construct(private readonly ExpressionLanguage $expressionLanguage)
    {
    }

    public function evaluate(string $expression, ProductInterface $product, CompletenessCheckContext $context): mixed
    {
        return $this->expressionLanguage->evaluate($expression, [
            'product' => $product,
            'channel' => $context->getChannel(),
            'locale' => $context->getLocale(),
            'channelCode' => $context->getChannelCode(),
            'localeCode' => $context->getLocaleCode(),
        ]);
    }
}

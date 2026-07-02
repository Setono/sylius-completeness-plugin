<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression;

use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Builds the shared ExpressionLanguage from the tagged function providers. A factory is needed
 * because the ExpressionLanguage constructor takes an array while the tagged services are an iterable
 */
final class ExpressionLanguageFactory
{
    private function __construct()
    {
    }

    /**
     * @param iterable<ExpressionFunctionProviderInterface> $providers
     */
    public static function create(iterable $providers): ExpressionLanguage
    {
        $expressionLanguage = new ExpressionLanguage();
        foreach ($providers as $provider) {
            $expressionLanguage->registerProvider($provider);
        }

        return $expressionLanguage;
    }
}

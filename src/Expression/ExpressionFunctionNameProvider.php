<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

final class ExpressionFunctionNameProvider implements ExpressionFunctionNameProviderInterface
{
    /**
     * @param iterable<ExpressionFunctionProviderInterface> $providers the same tagged providers the
     *                                                                  shared ExpressionLanguage is built from
     */
    public function __construct(private readonly iterable $providers)
    {
    }

    public function getNames(): array
    {
        $names = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->getFunctions() as $function) {
                if ($function instanceof ExpressionFunction) {
                    $names[$function->getName()] = true;
                }
            }
        }

        $names = array_keys($names);
        sort($names);

        return $names;
    }
}

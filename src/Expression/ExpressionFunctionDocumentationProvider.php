<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression;

use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

final class ExpressionFunctionDocumentationProvider implements ExpressionFunctionDocumentationProviderInterface
{
    /**
     * @param iterable<ExpressionFunctionProviderInterface> $providers the same tagged providers the
     *                                                                  shared ExpressionLanguage is built from
     */
    public function __construct(private readonly iterable $providers)
    {
    }

    public function getDocumentation(): array
    {
        $documentation = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->getFunctions() as $function) {
                // functions registered without documentation (e.g. by a host) simply have no entry
                if ($function instanceof DocumentedExpressionFunction) {
                    $documentation[$function->getName()] = [
                        'signature' => $function->getSignature(),
                        'description' => $function->getDescription(),
                    ];
                }
            }
        }

        return $documentation;
    }
}

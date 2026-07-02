<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression;

interface ExpressionFunctionDocumentationProviderInterface
{
    /**
     * Documentation for the built-in expression functions, keyed by function name. Used to enrich
     * the editor autocompletion with a signature and a one-line description. Functions registered by
     * a host application through the extension point simply have no entry here.
     *
     * @return array<string, array{signature: string, description: string}>
     */
    public function getDocumentation(): array;
}

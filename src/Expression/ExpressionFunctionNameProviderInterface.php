<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression;

interface ExpressionFunctionNameProviderInterface
{
    /**
     * The names of every registered expression function, sorted and deduplicated. Used to drive
     * editor autocompletion so it stays in sync with the actual (plugin + host) function catalog.
     *
     * @return list<string>
     */
    public function getNames(): array;
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Rollup;

use Setono\SyliusCompletenessPlugin\Calculator\Result\ContextResult;

/**
 * The rollup orchestrator: pre-filters N/A and excluded contexts and delegates to the
 * configured strategy
 */
interface RollupInterface
{
    /**
     * @param list<ContextResult> $contextResults
     *
     * @return int|null null when every context is N/A or excluded
     */
    public function rollup(array $contextResults): ?int;
}

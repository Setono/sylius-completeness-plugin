<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Message\Command;

use Setono\SyliusCompletenessPlugin\Message\CommandInterface;

/**
 * Recomputes every product's global completeness ratio from its EXISTING per context rows.
 * Dispatched when context settings change: thresholds/rollup weights changed, not the per
 * context evaluation itself, so a full re-evaluation is unnecessary
 */
final class RefreshCompletenessRollups implements CommandInterface
{
}

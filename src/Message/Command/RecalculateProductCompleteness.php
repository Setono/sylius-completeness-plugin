<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Message\Command;

use Setono\SyliusCompletenessPlugin\Message\CommandInterface;

final class RecalculateProductCompleteness implements CommandInterface
{
    public function __construct(
        public readonly int $productId,
        public readonly bool $bulk = false,
    ) {
    }
}

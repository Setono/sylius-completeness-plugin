<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Context;

use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Setono\SyliusCompletenessPlugin\Exception\NoActiveCalculationException;
use Symfony\Contracts\Service\ResetInterface;

final class CalculationContext implements CalculationContextInterface, ResetInterface
{
    private ?CompletenessCheckContext $context = null;

    public function set(?CompletenessCheckContext $context): void
    {
        $this->context = $context;
    }

    public function get(): CompletenessCheckContext
    {
        return $this->context ?? throw new NoActiveCalculationException();
    }

    public function reset(): void
    {
        $this->context = null;
    }
}

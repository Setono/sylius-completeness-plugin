<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Context;

use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Setono\SyliusCompletenessPlugin\Exception\NoActiveCalculationException;

/**
 * Holds the (channel, locale) context of the calculation currently in progress. The calculator publishes
 * the active context here before evaluating a product so that locale/channel implicit expression functions
 * can read it. The holder belongs to a single calculation run and is never shared across concurrent
 * calculations
 */
interface CalculationContextInterface
{
    public function set(?CompletenessCheckContext $context): void;

    /**
     * @throws NoActiveCalculationException if no calculation is active
     */
    public function get(): CompletenessCheckContext;
}

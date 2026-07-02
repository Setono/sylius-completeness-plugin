<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Exception;

/**
 * Thrown when a calculation scoped service is used outside a calculation
 */
final class NoActiveCalculationException extends \LogicException
{
    public function __construct()
    {
        parent::__construct('No calculation is active. Locale/channel implicit expression functions can only be used during a calculation');
    }
}

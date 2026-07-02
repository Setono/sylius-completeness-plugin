<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Exception;

/**
 * Thrown by a checker when its configuration is missing or invalid. The calculator
 * treats this like any other checker exception, i.e. the rule enters the "errored" state
 */
final class InvalidCheckerConfigurationException extends \InvalidArgumentException
{
}

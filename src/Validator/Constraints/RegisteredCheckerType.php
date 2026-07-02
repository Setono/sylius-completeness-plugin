<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class RegisteredCheckerType extends Constraint
{
    public string $message = 'setono_sylius_completeness.completeness_rule.type.not_registered';
}

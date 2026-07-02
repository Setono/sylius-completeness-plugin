<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class ExpressionRequiredWhenExpressionType extends Constraint
{
    public string $requiredMessage = 'setono_sylius_completeness.completeness_rule.expression.required';

    public string $notAllowedMessage = 'setono_sylius_completeness.completeness_rule.expression.not_allowed';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

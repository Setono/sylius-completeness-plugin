<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Validator\Constraints;

use Setono\SyliusCompletenessPlugin\Checker\ExpressionChecker;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRuleInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * The expression slot IS the check for expression type rules and meaningless for curated
 * checkers: required in the first case, not allowed in the second
 */
final class ExpressionRequiredWhenExpressionTypeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ExpressionRequiredWhenExpressionType) {
            throw new UnexpectedTypeException($constraint, ExpressionRequiredWhenExpressionType::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof CompletenessRuleInterface) {
            throw new UnexpectedValueException($value, CompletenessRuleInterface::class);
        }

        $expression = $value->getExpression();
        $hasExpression = null !== $expression && '' !== trim($expression);

        if (ExpressionChecker::TYPE === $value->getType() && !$hasExpression) {
            $this->context->buildViolation($constraint->requiredMessage)
                ->atPath('expression')
                ->addViolation();
        }

        if (ExpressionChecker::TYPE !== $value->getType() && $hasExpression) {
            $this->context->buildViolation($constraint->notAllowedMessage)
                ->atPath('expression')
                ->addViolation();
        }
    }
}

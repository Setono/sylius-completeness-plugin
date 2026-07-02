<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Validator\Constraints;

use Setono\SyliusCompletenessPlugin\Exception\InvalidExpressionException;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Compile validates a condition/expression at save time so authors get immediate feedback
 * on syntax errors, unknown functions and unknown variables
 */
final class ValidExpressionValidator extends ConstraintValidator
{
    public function __construct(private readonly ExpressionValidatorInterface $expressionValidator)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidExpression) {
            throw new UnexpectedTypeException($constraint, ValidExpression::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if ('' === trim($value)) {
            return;
        }

        try {
            $this->expressionValidator->validate($value);
        } catch (InvalidExpressionException $e) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ error }}', $e->getMessage())
                ->addViolation();
        }
    }
}

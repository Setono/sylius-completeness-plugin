<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Validator\Constraints;

use Sylius\Component\Registry\ServiceRegistryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class RegisteredCheckerTypeValidator extends ConstraintValidator
{
    public function __construct(private readonly ServiceRegistryInterface $checkerRegistry)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof RegisteredCheckerType) {
            throw new UnexpectedTypeException($constraint, RegisteredCheckerType::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if (!$this->checkerRegistry->has($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ type }}', $value)
                ->addViolation();
        }
    }
}

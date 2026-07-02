<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Validator\Constraints;

use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckerInterface;
use Setono\SyliusCompletenessPlugin\Checker\IsEnabledChecker;
use Setono\SyliusCompletenessPlugin\Validator\Constraints\RegisteredCheckerType;
use Setono\SyliusCompletenessPlugin\Validator\Constraints\RegisteredCheckerTypeValidator;
use Sylius\Component\Registry\ServiceRegistry;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<RegisteredCheckerTypeValidator>
 */
final class RegisteredCheckerTypeValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): RegisteredCheckerTypeValidator
    {
        $registry = new ServiceRegistry(CompletenessCheckerInterface::class, 'completeness checker');
        $registry->register('is_enabled', new IsEnabledChecker());

        return new RegisteredCheckerTypeValidator($registry);
    }

    /**
     * @test
     */
    public function it_accepts_a_registered_type(): void
    {
        $this->validator->validate('is_enabled', new RegisteredCheckerType());

        $this->assertNoViolation();
    }

    /**
     * @test
     */
    public function it_accepts_null(): void
    {
        $this->validator->validate(null, new RegisteredCheckerType());

        $this->assertNoViolation();
    }

    /**
     * @test
     */
    public function it_rejects_an_unregistered_type(): void
    {
        $constraint = new RegisteredCheckerType();

        $this->validator->validate('unknown_type', $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ type }}', 'unknown_type')
            ->assertRaised();
    }
}

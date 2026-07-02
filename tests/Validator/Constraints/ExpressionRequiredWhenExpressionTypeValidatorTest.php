<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Validator\Constraints;

use Setono\SyliusCompletenessPlugin\Model\CompletenessRule;
use Setono\SyliusCompletenessPlugin\Validator\Constraints\ExpressionRequiredWhenExpressionType;
use Setono\SyliusCompletenessPlugin\Validator\Constraints\ExpressionRequiredWhenExpressionTypeValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<ExpressionRequiredWhenExpressionTypeValidator>
 */
final class ExpressionRequiredWhenExpressionTypeValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ExpressionRequiredWhenExpressionTypeValidator
    {
        return new ExpressionRequiredWhenExpressionTypeValidator();
    }

    private function createRule(string $type, ?string $expression): CompletenessRule
    {
        $rule = new CompletenessRule();
        $rule->setType($type);
        $rule->setExpression($expression);

        return $rule;
    }

    /**
     * @test
     */
    public function it_requires_an_expression_for_expression_type_rules(): void
    {
        $constraint = new ExpressionRequiredWhenExpressionType();

        $this->validator->validate($this->createRule('expression', null), $constraint);

        $this->buildViolation($constraint->requiredMessage)
            ->atPath('property.path.expression')
            ->assertRaised();
    }

    /**
     * @test
     */
    public function it_rejects_an_expression_on_curated_checker_rules(): void
    {
        $constraint = new ExpressionRequiredWhenExpressionType();

        $this->validator->validate($this->createRule('has_name', 'true'), $constraint);

        $this->buildViolation($constraint->notAllowedMessage)
            ->atPath('property.path.expression')
            ->assertRaised();
    }

    /**
     * @test
     */
    public function it_accepts_valid_combinations(): void
    {
        $constraint = new ExpressionRequiredWhenExpressionType();

        $this->validator->validate($this->createRule('expression', 'true'), $constraint);
        $this->validator->validate($this->createRule('has_name', null), $constraint);

        $this->assertNoViolation();
    }
}

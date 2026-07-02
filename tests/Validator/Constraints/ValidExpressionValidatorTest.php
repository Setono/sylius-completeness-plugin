<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Validator\Constraints;

use Setono\SyliusCompletenessPlugin\Expression\ExpressionLanguageFactory;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionValidator;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\TextFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Validator\Constraints\ValidExpression;
use Setono\SyliusCompletenessPlugin\Validator\Constraints\ValidExpressionValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<ValidExpressionValidator>
 */
final class ValidExpressionValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ValidExpressionValidator
    {
        return new ValidExpressionValidator(new ExpressionValidator(ExpressionLanguageFactory::create([
            new TextFunctionsProvider(),
        ])));
    }

    /**
     * @test
     */
    public function it_accepts_a_valid_expression(): void
    {
        $this->validator->validate('word_count(product.getDescription()) >= 200', new ValidExpression());

        $this->assertNoViolation();
    }

    /**
     * @test
     */
    public function it_accepts_null_and_blank_values(): void
    {
        $this->validator->validate(null, new ValidExpression());
        $this->validator->validate('', new ValidExpression());

        $this->assertNoViolation();
    }

    /**
     * @test
     */
    public function it_rejects_an_invalid_expression(): void
    {
        $constraint = new ValidExpression();

        $this->validator->validate('unknown_function(product)', $constraint);

        $violations = $this->context->getViolations();
        self::assertCount(1, $violations);
        self::assertSame($constraint->message, $violations->get(0)->getMessageTemplate());
    }
}

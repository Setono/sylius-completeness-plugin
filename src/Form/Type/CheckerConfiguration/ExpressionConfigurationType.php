<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Form\Type\CheckerConfiguration;

use Setono\SyliusCompletenessPlugin\Validator\Constraints\ValidExpression;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * The configuration of the "Custom expression" checker: the ExpressionLanguage expression that is the
 * check itself. Being a checker configuration form, it is shown/hidden by the same client-side swap as
 * every other checker's configuration.
 *
 * @extends AbstractType<mixed>
 */
final class ExpressionConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('expression', TextareaType::class, [
            'label' => 'setono_sylius_completeness.form.completeness_rule.expression',
            'help' => 'setono_sylius_completeness.form.completeness_rule.expression_help',
            'attr' => ['rows' => 3, 'data-ssc-expression' => '1'],
            'constraints' => [
                new NotBlank(['message' => 'setono_sylius_completeness.completeness_rule.expression.required']),
                new ValidExpression(),
            ],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_completeness_checker_configuration_expression';
    }
}

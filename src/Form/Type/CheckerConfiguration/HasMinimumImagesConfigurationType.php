<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Form\Type\CheckerConfiguration;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<mixed>
 */
final class HasMinimumImagesConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('count', IntegerType::class, [
            'label' => 'setono_sylius_completeness.form.checker_configuration.minimum_image_count',
            'constraints' => [
                new NotBlank(),
                new GreaterThanOrEqual(1),
            ],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_completeness_checker_configuration_has_minimum_images';
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Form\Type;

use Sylius\Bundle\ProductBundle\Form\Type\ProductAutocompleteChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<mixed>
 */
final class PreviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', ProductAutocompleteChoiceType::class, [
                'label' => 'sylius.ui.product',
                'constraints' => [new NotBlank()],
            ])
            ->add('channelCode', ChannelCodeChoiceType::class, [
                'label' => 'sylius.ui.channel',
                'constraints' => [new NotBlank()],
            ])
            ->add('localeCode', LocaleCodeChoiceType::class, [
                'label' => 'sylius.ui.locale',
                'constraints' => [new NotBlank()],
            ])
            ->add('expression', TextareaType::class, [
                'label' => 'setono_sylius_completeness.ui.scratchpad',
                'required' => false,
                'help' => 'setono_sylius_completeness.form.preview.expression_help',
                'attr' => ['rows' => 2],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_completeness_preview';
    }
}

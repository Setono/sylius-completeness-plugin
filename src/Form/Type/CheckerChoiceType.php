<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
final class CheckerChoiceType extends AbstractType
{
    /**
     * @param array<string, string> $checkers checker type => label
     */
    public function __construct(private readonly array $checkers)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => array_flip($this->checkers),
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_completeness_checker_choice';
    }
}

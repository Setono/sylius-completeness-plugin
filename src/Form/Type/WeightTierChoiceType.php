<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
final class WeightTierChoiceType extends AbstractType
{
    /**
     * @param array<string, float> $weightTiers tier => resolved weight
     */
    public function __construct(private readonly array $weightTiers)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];
        foreach (array_keys($this->weightTiers) as $tier) {
            $choices['setono_sylius_completeness.ui.weight_tier.' . $tier] = $tier;
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_completeness_weight_tier_choice';
    }
}

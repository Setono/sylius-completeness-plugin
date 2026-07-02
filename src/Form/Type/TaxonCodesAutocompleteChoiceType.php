<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Form\Type;

use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonAutocompleteChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A multiple taxon autocomplete whose model data is a list of taxon CODES (the completeness rule
 * stores codes, not relations). Backed by Sylius' remote taxon autocomplete, so it scales to
 * catalogs with thousands of taxons.
 *
 * @extends AbstractType<mixed>
 */
final class TaxonCodesAutocompleteChoiceType extends AbstractType
{
    /**
     * @param DataTransformerInterface<mixed, mixed> $taxonsToCodesTransformer
     */
    public function __construct(private readonly DataTransformerInterface $taxonsToCodesTransformer)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Sylius' autocomplete works with taxon entities; the transformer maps them to/from the
        // list of codes the rule persists
        $builder->addModelTransformer($this->taxonsToCodesTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'multiple' => true,
        ]);
    }

    public function getParent(): string
    {
        return TaxonAutocompleteChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_completeness_taxon_codes_autocomplete_choice';
    }
}

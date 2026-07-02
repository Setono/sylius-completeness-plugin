<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Form\Type;

use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A choice of taxon CODES (the completeness entities store codes, not relations)
 *
 * @extends AbstractType<mixed>
 */
final class TaxonCodeChoiceType extends AbstractType
{
    /**
     * @param RepositoryInterface<TaxonInterface> $taxonRepository
     */
    public function __construct(private readonly RepositoryInterface $taxonRepository)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => $this->getChoices(),
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_completeness_taxon_code_choice';
    }

    /**
     * @return array<string, string>
     */
    private function getChoices(): array
    {
        $choices = [];
        foreach ($this->taxonRepository->findAll() as $taxon) {
            if (!$taxon instanceof TaxonInterface) {
                continue;
            }

            $code = $taxon->getCode();
            if (null === $code) {
                continue;
            }

            $choices[sprintf('%s (%s)', $taxon->getName() ?? $code, $code)] = $code;
        }

        return $choices;
    }
}

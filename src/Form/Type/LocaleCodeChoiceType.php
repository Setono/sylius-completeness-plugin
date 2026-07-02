<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Form\Type;

use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A choice of locale CODES (the completeness entities store codes, not relations)
 *
 * @extends AbstractType<mixed>
 */
final class LocaleCodeChoiceType extends AbstractType
{
    /**
     * @param RepositoryInterface<LocaleInterface> $localeRepository
     */
    public function __construct(private readonly RepositoryInterface $localeRepository)
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
        return 'setono_sylius_completeness_locale_code_choice';
    }

    /**
     * @return array<string, string>
     */
    private function getChoices(): array
    {
        $choices = [];
        foreach ($this->localeRepository->findAll() as $locale) {
            if (!$locale instanceof LocaleInterface) {
                continue;
            }

            $code = $locale->getCode();
            if (null === $code) {
                continue;
            }

            $choices[sprintf('%s (%s)', $locale->getName() ?? $code, $code)] = $code;
        }

        return $choices;
    }
}

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
     * The known groups, rendered as optgroups in this order; any other group follows and "Misc"
     * (the null group) is always last.
     */
    private const GROUP_ORDER = ['content', 'media', 'merchandising', 'seo'];

    /**
     * @param array<string, string> $checkers checker type => label
     * @param array<string, string|null> $groups checker type => group (null for the "Misc" group)
     */
    public function __construct(
        private readonly array $checkers,
        private readonly array $groups = [],
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => $this->buildGroupedChoices(),
        ]);
    }

    /**
     * Builds an optgroup structure: group label => (checker label => type).
     *
     * @return array<string, array<string, string>>
     */
    private function buildGroupedChoices(): array
    {
        /** @var array<string, array<string, string>> $grouped */
        $grouped = array_fill_keys(self::GROUP_ORDER, []);

        /** @var array<string, string> $misc */
        $misc = [];

        foreach ($this->checkers as $type => $label) {
            $group = $this->groups[$type] ?? null;
            if (null === $group) {
                $misc[$label] = $type;

                continue;
            }

            $grouped[$group][$label] = $type;
        }

        $choices = [];
        foreach ($grouped as $group => $items) {
            if ([] === $items) {
                continue;
            }

            $choices['setono_sylius_completeness.ui.checker_group.' . $group] = $items;
        }

        if ([] !== $misc) {
            $choices['setono_sylius_completeness.ui.checker_group.misc'] = $misc;
        }

        return $choices;
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

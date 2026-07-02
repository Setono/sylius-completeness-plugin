<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Form\Type;

use Setono\SyliusCompletenessPlugin\Form\Type\CheckerChoiceType;
use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

final class CheckerChoiceTypeTest extends TypeTestCase
{
    /**
     * @return list<\Symfony\Component\Form\FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                new CheckerChoiceType(
                    [
                        'has_name' => 'Has name',
                        'has_price' => 'Has price',
                        'has_image' => 'Has image',
                        'is_enabled' => 'Enabled',
                        'expression' => 'Expression',
                    ],
                    [
                        'has_name' => 'content',
                        'has_price' => 'merchandising',
                        'has_image' => 'media',
                        'is_enabled' => null,
                        'expression' => null,
                    ],
                ),
            ], []),
        ];
    }

    /**
     * @test
     */
    public function it_groups_checkers_into_optgroups_in_a_fixed_order_with_misc_last(): void
    {
        $view = $this->factory->create(CheckerChoiceType::class)->createView();

        // Symfony's FormView::$vars is an untyped public array, so phpstan cannot see the offset read
        /** @phpstan-ignore offsetAccess.nonOffsetAccessible */
        $choices = $view->vars['choices'];
        self::assertIsArray($choices);

        $groups = [];
        foreach ($choices as $choice) {
            self::assertInstanceOf(ChoiceGroupView::class, $choice);
            self::assertIsArray($choice->choices);
            self::assertIsString($choice->label);

            $values = [];
            foreach ($choice->choices as $choiceView) {
                self::assertInstanceOf(ChoiceView::class, $choiceView);
                self::assertIsString($choiceView->value);
                $values[] = $choiceView->value;
            }

            $groups[$choice->label] = $values;
        }

        self::assertSame([
            'setono_sylius_completeness.ui.checker_group.content' => ['has_name'],
            'setono_sylius_completeness.ui.checker_group.media' => ['has_image'],
            'setono_sylius_completeness.ui.checker_group.merchandising' => ['has_price'],
            'setono_sylius_completeness.ui.checker_group.misc' => ['is_enabled', 'expression'],
        ], $groups);
    }
}

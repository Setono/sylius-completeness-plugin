<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Form\Type;

use Setono\SyliusCompletenessPlugin\Checker\ExpressionChecker;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRuleInterface;
use Sylius\Bundle\ResourceBundle\Form\Registry\FormTypeRegistryInterface;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class CompletenessRuleType extends AbstractResourceType
{
    /**
     * @param string[] $validationGroups
     */
    public function __construct(
        string $dataClass,
        array $validationGroups,
        private readonly FormTypeRegistryInterface $formTypeRegistry,
        private readonly bool $enableCustomWeight,
    ) {
        parent::__construct($dataClass, $validationGroups);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'setono_sylius_completeness.form.completeness_rule.label',
            ])
            ->add('group', TextType::class, [
                'label' => 'setono_sylius_completeness.form.completeness_rule.group',
                'required' => false,
                'help' => 'setono_sylius_completeness.form.completeness_rule.group_help',
            ])
            ->add('type', CheckerChoiceType::class, [
                'label' => 'setono_sylius_completeness.ui.checker_type',
                'attr' => [
                    'data-form-collection' => 'update',
                ],
            ])
            ->add('weightTier', WeightTierChoiceType::class, [
                'label' => 'setono_sylius_completeness.form.completeness_rule.weight_tier',
                'help' => 'setono_sylius_completeness.form.completeness_rule.weight_tier_help',
            ])
            ->add('condition', TextareaType::class, [
                'label' => 'setono_sylius_completeness.form.completeness_rule.condition',
                'required' => false,
                'help' => 'setono_sylius_completeness.form.completeness_rule.condition_help',
                'attr' => ['rows' => 2],
            ])
            ->add('expression', TextareaType::class, [
                'label' => 'setono_sylius_completeness.form.completeness_rule.expression',
                'required' => false,
                'help' => 'setono_sylius_completeness.form.completeness_rule.expression_help',
                'attr' => ['rows' => 2],
            ])
            ->add('channelCodes', ChannelCodeChoiceType::class, [
                'label' => 'setono_sylius_completeness.form.completeness_rule.channels',
                'required' => false,
                'multiple' => true,
                'help' => 'setono_sylius_completeness.form.completeness_rule.channels_help',
            ])
            ->add('localeCodes', LocaleCodeChoiceType::class, [
                'label' => 'setono_sylius_completeness.form.completeness_rule.locales',
                'required' => false,
                'multiple' => true,
                'help' => 'setono_sylius_completeness.form.completeness_rule.locales_help',
            ])
            ->add('taxonCodes', TaxonCodesAutocompleteChoiceType::class, [
                'label' => 'setono_sylius_completeness.form.completeness_rule.taxons',
                'required' => false,
                'help' => 'setono_sylius_completeness.form.completeness_rule.taxons_help',
            ])
            ->add('position', IntegerType::class, [
                'label' => 'setono_sylius_completeness.form.completeness_rule.position',
                'help' => 'setono_sylius_completeness.form.completeness_rule.position_help',
                // the model property is a non nullable int, so an empty submission must not map null
                'empty_data' => '0',
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'sylius.ui.enabled',
                'required' => false,
            ])
            ->add('code', TextType::class, [
                'label' => 'setono_sylius_completeness.form.completeness_rule.code',
                'required' => false,
                'help' => 'setono_sylius_completeness.form.completeness_rule.code_help',
            ]);

        if ($this->enableCustomWeight) {
            $builder->add('customWeight', NumberType::class, [
                'label' => 'setono_sylius_completeness.form.completeness_rule.custom_weight',
                'required' => false,
                'help' => 'setono_sylius_completeness.form.completeness_rule.custom_weight_help',
            ]);
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $rule = $event->getData();
            if (!$rule instanceof CompletenessRuleInterface) {
                return;
            }

            $type = $rule->getType();
            if (null !== $type) {
                $this->addConfigurationField($event->getForm(), $type);
            }
        });

        // the no-JS server round-trip: the SUBMITTED type decides which configuration sub form
        // is added, so a plain form submit always produces (and validates) the right fields
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $type = $data['type'] ?? null;
            if (is_string($type) && '' !== $type) {
                $this->addConfigurationField($event->getForm(), $type);

                // the expression column only applies to the expression checker
                if (ExpressionChecker::TYPE !== $type) {
                    $data['expression'] = null;
                }
            }

            // auto generate the code from the label when left blank
            $code = $data['code'] ?? null;
            $label = $data['label'] ?? null;
            if ((!is_string($code) || '' === trim($code)) && is_string($label) && '' !== trim($label)) {
                $data['code'] = (new AsciiSlugger())->slug($label, '_')->lower()->toString();
            }

            $event->setData($data);
        });
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private function addConfigurationField(FormInterface $form, string $type): void
    {
        if (!$this->formTypeRegistry->has($type, 'default')) {
            return;
        }

        $form->add('configuration', $this->formTypeRegistry->get($type, 'default'), [
            'label' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_completeness_completeness_rule';
    }
}

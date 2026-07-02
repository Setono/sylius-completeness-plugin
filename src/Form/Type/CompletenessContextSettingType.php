<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Form\Type;

use Setono\SyliusCompletenessPlugin\Model\CompletenessContextSettingInterface;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

final class CompletenessContextSettingType extends AbstractResourceType
{
    /**
     * @param string[] $validationGroups
     */
    public function __construct(
        string $dataClass,
        array $validationGroups,
        private readonly int $defaultThreshold,
    ) {
        parent::__construct($dataClass, $validationGroups);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('channelCode', ChannelCodeChoiceType::class, [
                'label' => 'setono_sylius_completeness.form.context_setting.channel',
            ])
            ->add('localeCode', LocaleCodeChoiceType::class, [
                'label' => 'setono_sylius_completeness.form.context_setting.locale',
            ])
            ->add('threshold', IntegerType::class, [
                'label' => 'setono_sylius_completeness.form.context_setting.threshold',
                'required' => false,
                'help' => 'setono_sylius_completeness.form.context_setting.threshold_help',
                'attr' => [
                    'placeholder' => $this->defaultThreshold,
                ],
            ])
            ->add('countsTowardOverall', CheckboxType::class, [
                'label' => 'setono_sylius_completeness.form.context_setting.counts_toward_overall',
                'mapped' => false,
                'required' => false,
                'help' => 'setono_sylius_completeness.form.context_setting.counts_toward_overall_help',
            ])
            ->add('rollupWeight', NumberType::class, [
                'label' => 'setono_sylius_completeness.form.context_setting.rollup_weight',
                'required' => false,
                'help' => 'setono_sylius_completeness.form.context_setting.rollup_weight_help',
                // the model property is a non nullable float, so an empty submission must not map null
                'empty_data' => '1',
            ]);

        // "counts toward overall score" is sugar for rollupWeight 0 vs > 0
        $builder->addEventListener(FormEvents::POST_SET_DATA, static function (FormEvent $event): void {
            $setting = $event->getData();
            $rollupWeight = $setting instanceof CompletenessContextSettingInterface ? $setting->getRollupWeight() : 1.0;

            $event->getForm()->get('countsTowardOverall')->setData($rollupWeight > 0);
        });

        $builder->addEventListener(FormEvents::SUBMIT, static function (FormEvent $event): void {
            $setting = $event->getData();
            if (!$setting instanceof CompletenessContextSettingInterface) {
                return;
            }

            $countsTowardOverall = (bool) $event->getForm()->get('countsTowardOverall')->getData();

            if (!$countsTowardOverall) {
                $setting->setRollupWeight(0.0);
            } elseif ($setting->getRollupWeight() <= 0.0) {
                $setting->setRollupWeight(1.0);
            }
        });
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_completeness_context_setting';
    }
}

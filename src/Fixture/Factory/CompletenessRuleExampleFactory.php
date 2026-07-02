<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Fixture\Factory;

use Setono\SyliusCompletenessPlugin\Model\CompletenessRuleInterface;
use Sylius\Bundle\CoreBundle\Fixture\Factory\AbstractExampleFactory;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class CompletenessRuleExampleFactory extends AbstractExampleFactory
{
    private readonly OptionsResolver $optionsResolver;

    /**
     * @param FactoryInterface<CompletenessRuleInterface> $ruleFactory
     */
    public function __construct(private readonly FactoryInterface $ruleFactory)
    {
        $this->optionsResolver = new OptionsResolver();
        $this->configureOptions($this->optionsResolver);
    }

    /**
     * @param array<array-key, mixed> $options
     */
    public function create(array $options = []): CompletenessRuleInterface
    {
        /**
         * @var array{
         *     label: string,
         *     code: ?string,
         *     type: string,
         *     group: ?string,
         *     weight_tier: string,
         *     configuration: array<string, mixed>,
         *     condition: ?string,
         *     expression: ?string,
         *     channel_codes: list<string>,
         *     locale_codes: list<string>,
         *     taxon_codes: list<string>,
         *     custom_weight: ?float,
         *     enabled: bool,
         *     position: int,
         * } $options
         */
        $options = $this->optionsResolver->resolve($options);

        $rule = $this->ruleFactory->createNew();
        $rule->setLabel($options['label']);
        $rule->setCode($options['code'] ?? (new AsciiSlugger())->slug($options['label'], '_')->lower()->toString());
        $rule->setType($options['type']);
        $rule->setGroup($options['group']);
        $rule->setWeightTier($options['weight_tier']);
        $rule->setConfiguration($options['configuration']);
        $rule->setCondition($options['condition']);
        $rule->setExpression($options['expression']);
        $rule->setChannelCodes($options['channel_codes']);
        $rule->setLocaleCodes($options['locale_codes']);
        $rule->setTaxonCodes($options['taxon_codes']);
        $rule->setCustomWeight($options['custom_weight']);
        $rule->setEnabled($options['enabled']);
        $rule->setPosition($options['position']);

        return $rule;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('label')
            ->setAllowedTypes('label', 'string')
            ->setDefault('code', null)
            ->setAllowedTypes('code', ['null', 'string'])
            ->setDefault('type', 'is_enabled')
            ->setAllowedTypes('type', 'string')
            ->setDefault('group', null)
            ->setAllowedTypes('group', ['null', 'string'])
            ->setDefault('weight_tier', CompletenessRuleInterface::WEIGHT_TIER_MEDIUM)
            ->setAllowedTypes('weight_tier', 'string')
            ->setDefault('configuration', [])
            ->setAllowedTypes('configuration', 'array')
            ->setDefault('condition', null)
            ->setAllowedTypes('condition', ['null', 'string'])
            ->setDefault('expression', null)
            ->setAllowedTypes('expression', ['null', 'string'])
            ->setDefault('channel_codes', [])
            ->setAllowedTypes('channel_codes', 'string[]')
            ->setDefault('locale_codes', [])
            ->setAllowedTypes('locale_codes', 'string[]')
            ->setDefault('taxon_codes', [])
            ->setAllowedTypes('taxon_codes', 'string[]')
            ->setDefault('custom_weight', null)
            ->setAllowedTypes('custom_weight', ['null', 'float'])
            ->setDefault('enabled', true)
            ->setAllowedTypes('enabled', 'bool')
            ->setDefault('position', 0)
            ->setAllowedTypes('position', 'int')
        ;
    }
}

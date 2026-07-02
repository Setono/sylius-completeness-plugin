<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\AbstractResourceFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class CompletenessRuleFixture extends AbstractResourceFixture
{
    public function getName(): string
    {
        return 'setono_sylius_completeness_rule';
    }

    protected function configureResourceNode(ArrayNodeDefinition $resourceNode): void
    {
        $resourceNode
            ->children()
                ->scalarNode('label')->cannotBeEmpty()->end()
                ->scalarNode('code')->end()
                ->scalarNode('type')->cannotBeEmpty()->end()
                ->scalarNode('group')->end()
                ->scalarNode('weight_tier')->end()
                ->variableNode('configuration')->end()
                ->scalarNode('condition')->end()
                ->scalarNode('expression')->end()
                ->arrayNode('channel_codes')->scalarPrototype()->end()->end()
                ->arrayNode('locale_codes')->scalarPrototype()->end()->end()
                ->arrayNode('taxon_codes')->scalarPrototype()->end()->end()
                ->floatNode('custom_weight')->end()
                ->booleanNode('enabled')->end()
                ->integerNode('position')->end()
        ;
    }
}

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
                ->scalarNode('channel_code')->end()
                ->scalarNode('locale_code')->end()
                ->scalarNode('taxon_code')->end()
                ->floatNode('custom_weight')->end()
                ->booleanNode('enabled')->end()
                ->integerNode('position')->end()
        ;
    }
}

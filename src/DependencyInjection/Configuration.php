<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\DependencyInjection;

use Setono\SyliusCompletenessPlugin\Form\Type\CompletenessContextType;
use Setono\SyliusCompletenessPlugin\Form\Type\CompletenessRuleType;
use Setono\SyliusCompletenessPlugin\Model\CompletenessContext;
use Setono\SyliusCompletenessPlugin\Model\CompletenessContextInterface;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRule;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRuleInterface;
use Setono\SyliusCompletenessPlugin\Model\ProductCompleteness;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessInterface;
use Setono\SyliusCompletenessPlugin\Model\RubricVersion;
use Setono\SyliusCompletenessPlugin\Model\RubricVersionInterface;
use Setono\SyliusCompletenessPlugin\Repository\CompletenessContextRepository;
use Setono\SyliusCompletenessPlugin\Repository\CompletenessRuleRepository;
use Setono\SyliusCompletenessPlugin\Repository\ProductCompletenessRepository;
use Setono\SyliusCompletenessPlugin\Repository\RubricVersionRepository;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Bundle\ResourceBundle\Form\Type\DefaultResourceType;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('setono_sylius_completeness');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('driver')
                    ->defaultValue(SyliusResourceBundle::DRIVER_DOCTRINE_ORM)
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('rollup_strategy')
                    ->info('How per-context ratios are collapsed into the single product ratio: weighted_average, minimum, default_channel, or the name of a custom strategy')
                    ->defaultValue('weighted_average')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('default_channel_code')
                    ->info('The channel used by the default_channel rollup strategy. When null, the strategy falls back to a weighted average over all contexts')
                    ->defaultNull()
                ->end()
                ->integerNode('default_ready_threshold')
                    ->info('The green/"ready" line (0-100) used when a context has no per-context override')
                    ->defaultValue(80)
                    ->min(0)
                    ->max(100)
                ->end()
                ->integerNode('amber_band')
                    ->info('Width of the amber zone below the threshold (0 disables amber)')
                    ->defaultValue(20)
                    ->min(0)
                ->end()
                ->arrayNode('weight_tiers')
                    ->info('Map of weight tier => resolved weight. Notice that if you configure this node, it replaces the default tiers entirely')
                    ->useAttributeAsKey('tier')
                    ->floatPrototype()->min(0)->end()
                    ->defaultValue([
                        'low' => 1.0,
                        'medium' => 3.0,
                        'high' => 6.0,
                        'critical' => 10.0,
                    ])
                ->end()
                ->booleanNode('enable_custom_weight')
                    ->info('Exposes the advanced per-rule float weight override in the rule form')
                    ->defaultFalse()
                ->end()
                ->booleanNode('recalculate_on_doctrine_flush')
                    ->info('Whether a Doctrine flush marks the affected products dirty for the background recalculation drain')
                    ->defaultTrue()
                ->end()
                ->integerNode('recalculation_lock_ttl')
                    ->info('Lease (seconds) of the lock guarding the background drain (setono:completeness:process); it is refreshed every chunk, so this only needs to exceed the time to process one chunk')
                    ->defaultValue(900)
                    ->min(1)
                ->end()
            ->end();

        $this->addResourcesSection($rootNode);

        return $treeBuilder;
    }

    private function addResourcesSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('completeness_rule')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(CompletenessRule::class)->cannotBeEmpty()->end()
                                        ->scalarNode('interface')->defaultValue(CompletenessRuleInterface::class)->cannotBeEmpty()->end()
                                        ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultValue(CompletenessRuleRepository::class)->cannotBeEmpty()->end()
                                        ->scalarNode('factory')->defaultValue(Factory::class)->end()
                                        ->scalarNode('form')->defaultValue(CompletenessRuleType::class)->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('product_completeness')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(ProductCompleteness::class)->cannotBeEmpty()->end()
                                        ->scalarNode('interface')->defaultValue(ProductCompletenessInterface::class)->cannotBeEmpty()->end()
                                        ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultValue(ProductCompletenessRepository::class)->cannotBeEmpty()->end()
                                        ->scalarNode('factory')->defaultValue(Factory::class)->end()
                                        ->scalarNode('form')->defaultValue(DefaultResourceType::class)->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('context')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(CompletenessContext::class)->cannotBeEmpty()->end()
                                        ->scalarNode('interface')->defaultValue(CompletenessContextInterface::class)->cannotBeEmpty()->end()
                                        ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultValue(CompletenessContextRepository::class)->cannotBeEmpty()->end()
                                        ->scalarNode('factory')->defaultValue(Factory::class)->end()
                                        ->scalarNode('form')->defaultValue(CompletenessContextType::class)->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('rubric_version')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(RubricVersion::class)->cannotBeEmpty()->end()
                                        ->scalarNode('interface')->defaultValue(RubricVersionInterface::class)->cannotBeEmpty()->end()
                                        ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultValue(RubricVersionRepository::class)->cannotBeEmpty()->end()
                                        ->scalarNode('factory')->defaultValue(Factory::class)->end()
                                        ->scalarNode('form')->defaultValue(DefaultResourceType::class)->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}

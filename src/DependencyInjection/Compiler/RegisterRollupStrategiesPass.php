<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\DependencyInjection\Compiler;

use Setono\SyliusCompletenessPlugin\Rollup\Rollup;
use Setono\SyliusCompletenessPlugin\Rollup\RollupStrategyInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Builds the name => strategy service locator injected into the rollup, keyed by each strategy's
 * static getName(). An explicit compiler pass is used on purpose instead of a tagged locator argument
 */
final class RegisterRollupStrategiesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(Rollup::class)) {
            return;
        }

        /** @var array<string, Reference> $strategies */
        $strategies = [];
        foreach (array_keys($container->findTaggedServiceIds('setono_sylius_completeness.rollup_strategy', true)) as $serviceId) {
            $class = $container->getDefinition($serviceId)->getClass();
            if (!is_string($class) || !is_a($class, RollupStrategyInterface::class, true)) {
                throw new \InvalidArgumentException(sprintf(
                    'The service "%s" is tagged as a rollup strategy, so its class must implement %s',
                    $serviceId,
                    RollupStrategyInterface::class,
                ));
            }

            $strategies[$class::getName()] = new Reference($serviceId);
        }

        $locator = ServiceLocatorTagPass::register($container, $strategies);

        $container->getDefinition(Rollup::class)->setArgument(0, $locator);
    }
}

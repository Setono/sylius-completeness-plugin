<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\DependencyInjection\Compiler;

use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects services tagged setono_sylius_completeness.checker into the checker registry and
 * builds the type => label map used by the admin. If two checkers share a type, the LAST
 * registered service wins - this is how a host replaces a built-in checker
 */
final class RegisterCheckersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('setono_sylius_completeness.registry.checker')) {
            return;
        }

        $registry = $container->getDefinition('setono_sylius_completeness.registry.checker');

        /** @var array<string, array{serviceId: string, label: string}> $checkers */
        $checkers = [];

        foreach ($container->findTaggedServiceIds('setono_sylius_completeness.checker', true) as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                /** @var array{type?: string, label?: string} $attributes */
                $type = $attributes['type'] ?? $this->resolveType($container, $serviceId);
                $label = $attributes['label'] ?? ucfirst(str_replace('_', ' ', $type));

                // later registrations overwrite earlier ones => last registered wins
                $checkers[$type] = ['serviceId' => $serviceId, 'label' => $label];
            }
        }

        $labels = [];
        foreach ($checkers as $type => ['serviceId' => $serviceId, 'label' => $label]) {
            $registry->addMethodCall('register', [$type, new Reference($serviceId)]);
            $labels[$type] = $label;
        }

        $container->setParameter('setono_sylius_completeness.checkers', $labels);
    }

    private function resolveType(ContainerBuilder $container, string $serviceId): string
    {
        $class = $container->getDefinition($serviceId)->getClass() ?? $serviceId;
        $class = $container->getParameterBag()->resolveValue($class);

        if (!is_string($class) || !is_a($class, CompletenessCheckerInterface::class, true)) {
            throw new \InvalidArgumentException(sprintf(
                'The service "%s" is tagged as a completeness checker, but its class does not implement %s so the checker type cannot be resolved. Either implement the interface or add a "type" attribute to the tag',
                $serviceId,
                CompletenessCheckerInterface::class,
            ));
        }

        return $class::getType();
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\DependencyInjection\Compiler;

use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckerInterface;
use Setono\SyliusCompletenessPlugin\Form\Type\CheckerConfiguration\DefaultConfigurationType;
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

        /** @var array<string, array{serviceId: string, label: string, group: ?string}> $checkers */
        $checkers = [];

        foreach ($container->findTaggedServiceIds('setono_sylius_completeness.checker', true) as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                /** @var array{type?: string, label?: string, group?: string} $attributes */
                $type = $attributes['type'] ?? $this->resolveType($container, $serviceId);
                $label = $attributes['label'] ?? ucfirst(str_replace('_', ' ', $type));
                $group = $attributes['group'] ?? $this->resolveGroup($container, $serviceId);

                // later registrations overwrite earlier ones => last registered wins
                $checkers[$type] = ['serviceId' => $serviceId, 'label' => $label, 'group' => $group];
            }
        }

        $labels = [];
        $groups = [];
        foreach ($checkers as $type => ['serviceId' => $serviceId, 'label' => $label, 'group' => $group]) {
            $registry->addMethodCall('register', [$type, new Reference($serviceId)]);
            $labels[$type] = $label;
            $groups[$type] = $group;
        }

        $container->setParameter('setono_sylius_completeness.checkers', $labels);
        $container->setParameter('setono_sylius_completeness.checker_groups', $groups);

        $this->registerConfigurationFormTypes($container, array_keys($checkers));
    }

    /**
     * Maps every checker type to its configuration form type. Parameterless checkers fall back
     * to the shared empty DefaultConfigurationType
     *
     * @param list<string> $types
     */
    private function registerConfigurationFormTypes(ContainerBuilder $container, array $types): void
    {
        if (!$container->hasDefinition('setono_sylius_completeness.form_registry.checker_configuration')) {
            return;
        }

        $formRegistry = $container->getDefinition('setono_sylius_completeness.form_registry.checker_configuration');

        /** @var array<string, string> $formTypes */
        $formTypes = [];
        foreach ($container->findTaggedServiceIds('setono_sylius_completeness.checker_configuration_form_type', true) as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                /** @var array{type?: string} $attributes */
                if (!isset($attributes['type'])) {
                    throw new \InvalidArgumentException(sprintf(
                        'The service "%s" is tagged as a checker configuration form type, so it needs a "type" attribute on the tag',
                        $serviceId,
                    ));
                }

                $class = $container->getDefinition($serviceId)->getClass();
                if (!is_string($class)) {
                    throw new \InvalidArgumentException(sprintf('The service "%s" has no class', $serviceId));
                }

                $formTypes[$attributes['type']] = $class;
            }
        }

        foreach ($types as $type) {
            $formRegistry->addMethodCall('add', [$type, 'default', $formTypes[$type] ?? DefaultConfigurationType::class]);
        }
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

    private function resolveGroup(ContainerBuilder $container, string $serviceId): ?string
    {
        $class = $container->getDefinition($serviceId)->getClass() ?? $serviceId;
        $class = $container->getParameterBag()->resolveValue($class);

        // a checker registered by tag alone (not implementing the interface) simply has no group
        if (!is_string($class) || !is_a($class, CompletenessCheckerInterface::class, true)) {
            return null;
        }

        return $class::getGroup();
    }
}

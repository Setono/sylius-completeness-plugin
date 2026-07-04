<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\DependencyInjection\Compiler;

use Setono\SyliusCompletenessPlugin\Expression\ExpressionFunctionDocumentationProvider;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionFunctionNameProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Wires every service tagged setono_sylius_completeness.expression_function_provider into the shared
 * ExpressionLanguage and the function name/documentation providers. An explicit compiler pass is used
 * on purpose instead of a tagged iterator argument
 */
final class RegisterExpressionFunctionProvidersPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        $providers = $this->findAndSortTaggedServices('setono_sylius_completeness.expression_function_provider', $container);

        // the ExpressionLanguage is built through a factory, so index 0 is the factory method argument
        foreach ([
            'setono_sylius_completeness.expression_language',
            ExpressionFunctionNameProvider::class,
            ExpressionFunctionDocumentationProvider::class,
        ] as $consumerId) {
            if ($container->hasDefinition($consumerId)) {
                $container->getDefinition($consumerId)->setArgument(0, $providers);
            }
        }
    }
}

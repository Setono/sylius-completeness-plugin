<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\DependencyInjection\Compiler;

use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\AffectedProductsProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collects every service tagged setono_sylius_completeness.affected_products_resolver and injects them
 * into the affected products provider. An explicit compiler pass is used on purpose instead of a
 * tagged iterator argument. Registration order is preserved so the last matching resolver wins
 */
final class RegisterAffectedProductsResolversPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(AffectedProductsProvider::class)) {
            return;
        }

        $resolvers = $this->findAndSortTaggedServices('setono_sylius_completeness.affected_products_resolver', $container);

        $container->getDefinition(AffectedProductsProvider::class)->setArgument(0, $resolvers);
    }
}

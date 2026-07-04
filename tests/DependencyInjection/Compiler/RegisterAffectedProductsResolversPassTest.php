<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Setono\SyliusCompletenessPlugin\DependencyInjection\Compiler\RegisterAffectedProductsResolversPass;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\AffectedProductsProvider;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\ProductResolver;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\ProductVariantResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterAffectedProductsResolversPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterAffectedProductsResolversPass());
    }

    /**
     * @test
     */
    public function it_injects_tagged_resolvers_as_argument_zero_and_keeps_the_later_arguments(): void
    {
        $product = new Definition(ProductResolver::class);
        $product->addTag('setono_sylius_completeness.affected_products_resolver');
        $this->setDefinition('app.resolver.product', $product);

        $variant = new Definition(ProductVariantResolver::class);
        $variant->addTag('setono_sylius_completeness.affected_products_resolver');
        $this->setDefinition('app.resolver.variant', $variant);

        // argument 0 is a placeholder the pass overwrites; the doctrine argument must stay at index 1
        $provider = new Definition(AffectedProductsProvider::class);
        $provider->setArguments([[], new Reference('doctrine')]);
        $this->setDefinition(AffectedProductsProvider::class, $provider);

        $this->compile();

        $definition = $this->container->getDefinition(AffectedProductsProvider::class);

        $resolvers = $definition->getArgument(0);
        self::assertIsArray($resolvers);
        self::assertEquals(
            [new Reference('app.resolver.product'), new Reference('app.resolver.variant')],
            array_values($resolvers),
        );
        self::assertEquals(new Reference('doctrine'), $definition->getArgument(1));
    }

    /**
     * @test
     */
    public function it_does_nothing_when_the_provider_is_not_defined(): void
    {
        $product = new Definition(ProductResolver::class);
        $product->addTag('setono_sylius_completeness.affected_products_resolver');
        $this->setDefinition('app.resolver.product', $product);

        $this->compile();

        self::assertFalse($this->container->hasDefinition(AffectedProductsProvider::class));
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Setono\SyliusCompletenessPlugin\DependencyInjection\Compiler\RegisterExpressionFunctionProvidersPass;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionFunctionDocumentationProvider;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionFunctionNameProvider;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\CollectionFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\TextFunctionsProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class RegisterExpressionFunctionProvidersPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterExpressionFunctionProvidersPass());
    }

    /**
     * @test
     */
    public function it_injects_the_tagged_providers_into_every_consumer_in_registration_order(): void
    {
        $text = new Definition(TextFunctionsProvider::class);
        $text->addTag('setono_sylius_completeness.expression_function_provider');
        $this->setDefinition('app.expression_function_provider.text', $text);

        $collection = new Definition(CollectionFunctionsProvider::class);
        $collection->addTag('setono_sylius_completeness.expression_function_provider');
        $this->setDefinition('app.expression_function_provider.collection', $collection);

        $this->setDefinition('setono_sylius_completeness.expression_language', new Definition(ExpressionLanguage::class));
        $this->setDefinition(ExpressionFunctionNameProvider::class, new Definition(ExpressionFunctionNameProvider::class));
        $this->setDefinition(ExpressionFunctionDocumentationProvider::class, new Definition(ExpressionFunctionDocumentationProvider::class));

        $this->compile();

        $expected = [
            new Reference('app.expression_function_provider.text'),
            new Reference('app.expression_function_provider.collection'),
        ];

        foreach ([
            'setono_sylius_completeness.expression_language',
            ExpressionFunctionNameProvider::class,
            ExpressionFunctionDocumentationProvider::class,
        ] as $consumerId) {
            $argument = $this->container->getDefinition($consumerId)->getArgument(0);
            self::assertIsArray($argument);
            self::assertEquals($expected, array_values($argument));
        }
    }

    /**
     * @test
     */
    public function it_tolerates_a_missing_consumer(): void
    {
        $text = new Definition(TextFunctionsProvider::class);
        $text->addTag('setono_sylius_completeness.expression_function_provider');
        $this->setDefinition('app.expression_function_provider.text', $text);

        // only the name provider is registered; the pass must not fail over the missing others
        $this->setDefinition(ExpressionFunctionNameProvider::class, new Definition(ExpressionFunctionNameProvider::class));

        $this->compile();

        $providers = $this->container->getDefinition(ExpressionFunctionNameProvider::class)->getArgument(0);
        self::assertIsArray($providers);
        self::assertEquals(
            [new Reference('app.expression_function_provider.text')],
            array_values($providers),
        );
    }
}

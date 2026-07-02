<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Setono\SyliusCompletenessPlugin\Checker\HasImageChecker;
use Setono\SyliusCompletenessPlugin\Checker\IsEnabledChecker;
use Setono\SyliusCompletenessPlugin\DependencyInjection\Compiler\RegisterCheckersPass;
use Sylius\Component\Registry\ServiceRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterCheckersPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterCheckersPass());
    }

    private function registerRegistry(): void
    {
        $this->setDefinition('setono_sylius_completeness.registry.checker', new Definition(ServiceRegistry::class));
    }

    /**
     * @test
     */
    public function it_registers_tagged_checkers_in_the_registry(): void
    {
        $this->registerRegistry();

        $checker = new Definition(IsEnabledChecker::class);
        $checker->addTag('setono_sylius_completeness.checker', ['type' => 'is_enabled', 'label' => 'Enabled']);
        $this->setDefinition('app.checker.is_enabled', $checker);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'setono_sylius_completeness.registry.checker',
            'register',
            ['is_enabled', new Reference('app.checker.is_enabled')],
        );
        $this->assertContainerBuilderHasParameter('setono_sylius_completeness.checkers', ['is_enabled' => 'Enabled']);
    }

    /**
     * @test
     */
    public function it_lets_the_last_registered_checker_win_for_a_shared_type(): void
    {
        $this->registerRegistry();

        $builtIn = new Definition(HasImageChecker::class);
        $builtIn->addTag('setono_sylius_completeness.checker', ['type' => 'has_image', 'label' => 'Built in']);
        $this->setDefinition('setono_sylius_completeness.checker.has_image', $builtIn);

        $override = new Definition(HasImageChecker::class);
        $override->addTag('setono_sylius_completeness.checker', ['type' => 'has_image', 'label' => 'Host override']);
        $this->setDefinition('app.checker.has_image', $override);

        $this->compile();

        $registry = $this->container->getDefinition('setono_sylius_completeness.registry.checker');
        $registerCalls = array_filter($registry->getMethodCalls(), static fn (mixed $call): bool => is_array($call) && 'register' === $call[0]);

        self::assertCount(1, $registerCalls);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'setono_sylius_completeness.registry.checker',
            'register',
            ['has_image', new Reference('app.checker.has_image')],
        );
        $this->assertContainerBuilderHasParameter('setono_sylius_completeness.checkers', ['has_image' => 'Host override']);
    }

    /**
     * @test
     */
    public function it_resolves_the_type_from_the_checker_class_when_the_tag_has_no_type(): void
    {
        $this->registerRegistry();

        $checker = new Definition(IsEnabledChecker::class);
        $checker->addTag('setono_sylius_completeness.checker');
        $this->setDefinition('app.checker.autoconfigured', $checker);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'setono_sylius_completeness.registry.checker',
            'register',
            ['is_enabled', new Reference('app.checker.autoconfigured')],
        );
        $this->assertContainerBuilderHasParameter('setono_sylius_completeness.checkers', ['is_enabled' => 'Is enabled']);
    }

    /**
     * @test
     */
    public function it_throws_when_the_type_cannot_be_resolved(): void
    {
        $this->registerRegistry();

        $checker = new Definition(\stdClass::class);
        $checker->addTag('setono_sylius_completeness.checker');
        $this->setDefinition('app.checker.invalid', $checker);

        $this->expectException(\InvalidArgumentException::class);

        $this->compile();
    }

    /**
     * @test
     */
    public function it_does_nothing_when_the_registry_is_not_defined(): void
    {
        $checker = new Definition(IsEnabledChecker::class);
        $checker->addTag('setono_sylius_completeness.checker', ['type' => 'is_enabled']);
        $this->setDefinition('app.checker.is_enabled', $checker);

        $this->compile();

        self::assertFalse($this->container->hasParameter('setono_sylius_completeness.checkers'));
    }
}

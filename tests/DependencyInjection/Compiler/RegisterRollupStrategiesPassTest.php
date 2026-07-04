<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Setono\SyliusCompletenessPlugin\DependencyInjection\Compiler\RegisterRollupStrategiesPass;
use Setono\SyliusCompletenessPlugin\Rollup\MinimumRollupStrategy;
use Setono\SyliusCompletenessPlugin\Rollup\Rollup;
use Setono\SyliusCompletenessPlugin\Rollup\WeightedAverageRollupStrategy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterRollupStrategiesPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterRollupStrategiesPass());
    }

    /**
     * @test
     */
    public function it_builds_a_locator_for_the_rollup_keyed_by_the_strategy_name(): void
    {
        $weightedAverage = new Definition(WeightedAverageRollupStrategy::class);
        $weightedAverage->addTag('setono_sylius_completeness.rollup_strategy');
        $this->setDefinition('app.rollup_strategy.weighted_average', $weightedAverage);

        $minimum = new Definition(MinimumRollupStrategy::class);
        $minimum->addTag('setono_sylius_completeness.rollup_strategy');
        $this->setDefinition('app.rollup_strategy.minimum', $minimum);

        // argument 0 is a placeholder the pass overwrites with the locator; the strategy name stays at index 1
        $rollup = new Definition(Rollup::class);
        $rollup->setArguments([[], 'weighted_average']);
        $this->setDefinition(Rollup::class, $rollup);

        $this->compile();

        $definition = $this->container->getDefinition(Rollup::class);

        $locatorReference = $definition->getArgument(0);
        self::assertInstanceOf(Reference::class, $locatorReference);
        self::assertSame('weighted_average', $definition->getArgument(1));

        $locator = $this->container->getDefinition((string) $locatorReference);
        $map = $locator->getArgument(0);
        self::assertIsArray($map);
        self::assertSame(['weighted_average', 'minimum'], array_keys($map));
    }

    /**
     * @test
     */
    public function it_throws_when_a_tagged_service_is_not_a_rollup_strategy(): void
    {
        $invalid = new Definition(\stdClass::class);
        $invalid->addTag('setono_sylius_completeness.rollup_strategy');
        $this->setDefinition('app.rollup_strategy.invalid', $invalid);

        $rollup = new Definition(Rollup::class);
        $rollup->setArguments([[], 'weighted_average']);
        $this->setDefinition(Rollup::class, $rollup);

        $this->expectException(\InvalidArgumentException::class);

        $this->compile();
    }

    /**
     * @test
     */
    public function it_does_nothing_when_the_rollup_is_not_defined(): void
    {
        $weightedAverage = new Definition(WeightedAverageRollupStrategy::class);
        $weightedAverage->addTag('setono_sylius_completeness.rollup_strategy');
        $this->setDefinition('app.rollup_strategy.weighted_average', $weightedAverage);

        $this->compile();

        self::assertFalse($this->container->hasDefinition(Rollup::class));
    }
}

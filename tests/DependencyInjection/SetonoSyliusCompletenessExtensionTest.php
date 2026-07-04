<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Setono\SyliusCompletenessPlugin\DependencyInjection\SetonoSyliusCompletenessExtension;

final class SetonoSyliusCompletenessExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new SetonoSyliusCompletenessExtension(),
        ];
    }

    /**
     * @test
     */
    public function after_loading_the_correct_parameters_have_been_set(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('setono_sylius_completeness.rollup_strategy', 'weighted_average');
        $this->assertContainerBuilderHasParameter('setono_sylius_completeness.default_channel_code', null);
        $this->assertContainerBuilderHasParameter('setono_sylius_completeness.default_ready_threshold', 80);
        $this->assertContainerBuilderHasParameter('setono_sylius_completeness.amber_band', 20);
        $this->assertContainerBuilderHasParameter('setono_sylius_completeness.weight_tiers', [
            'low' => 1.0,
            'medium' => 3.0,
            'high' => 6.0,
            'critical' => 10.0,
        ]);
        $this->assertContainerBuilderHasParameter('setono_sylius_completeness.enable_custom_weight', false);
        $this->assertContainerBuilderHasParameter('setono_sylius_completeness.recalculate_on_doctrine_flush', true);
        $this->assertContainerBuilderHasParameter('setono_sylius_completeness.recalculation_lock_ttl', 900);
    }

    /**
     * @test
     */
    public function after_loading_the_resources_have_been_registered(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter(
            'setono_sylius_completeness.model.completeness_rule.class',
            \Setono\SyliusCompletenessPlugin\Model\CompletenessRule::class,
        );
        $this->assertContainerBuilderHasParameter(
            'setono_sylius_completeness.model.product_completeness.class',
            \Setono\SyliusCompletenessPlugin\Model\ProductCompleteness::class,
        );
        $this->assertContainerBuilderHasParameter(
            'setono_sylius_completeness.model.context.class',
            \Setono\SyliusCompletenessPlugin\Model\CompletenessContext::class,
        );
    }
}

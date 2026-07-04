<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\DependencyInjection\Configuration;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }

    /**
     * @test
     */
    public function it_has_sensible_defaults(): void
    {
        $this->assertProcessedConfigurationEquals([[]], ['rollup_strategy' => 'weighted_average'], 'rollup_strategy');
        $this->assertProcessedConfigurationEquals([[]], ['default_channel_code' => null], 'default_channel_code');
        $this->assertProcessedConfigurationEquals([[]], ['default_ready_threshold' => 80], 'default_ready_threshold');
        $this->assertProcessedConfigurationEquals([[]], ['amber_band' => 20], 'amber_band');
        $this->assertProcessedConfigurationEquals([[]], [
            'weight_tiers' => [
                'low' => 1.0,
                'medium' => 3.0,
                'high' => 6.0,
                'critical' => 10.0,
            ],
        ], 'weight_tiers');
        $this->assertProcessedConfigurationEquals([[]], ['enable_custom_weight' => false], 'enable_custom_weight');
        $this->assertProcessedConfigurationEquals([[]], ['recalculate_on_doctrine_flush' => true], 'recalculate_on_doctrine_flush');
        $this->assertProcessedConfigurationEquals([[]], ['recalculation_lock_ttl' => 900], 'recalculation_lock_ttl');
    }

    /**
     * @test
     */
    public function it_replaces_weight_tiers_when_configured(): void
    {
        $this->assertProcessedConfigurationEquals([
            ['weight_tiers' => ['must' => 5.0, 'nice' => 1.0]],
        ], [
            'weight_tiers' => ['must' => 5.0, 'nice' => 1.0],
        ], 'weight_tiers');
    }

    /**
     * @test
     */
    public function it_does_not_allow_ready_threshold_above_100(): void
    {
        $this->assertConfigurationIsInvalid([
            ['default_ready_threshold' => 101],
        ]);
    }

    /**
     * @test
     */
    public function it_does_not_allow_negative_amber_band(): void
    {
        $this->assertConfigurationIsInvalid([
            ['amber_band' => -1],
        ]);
    }

    /**
     * @test
     */
    public function it_does_not_allow_recalculation_lock_ttl_below_1(): void
    {
        $this->assertConfigurationIsInvalid([
            ['recalculation_lock_ttl' => 0],
        ]);
    }
}

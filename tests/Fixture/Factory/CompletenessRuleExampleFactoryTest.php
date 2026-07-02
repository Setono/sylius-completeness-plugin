<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Fixture\Factory;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusCompletenessPlugin\Fixture\Factory\CompletenessRuleExampleFactory;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRule;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class CompletenessRuleExampleFactoryTest extends TestCase
{
    use ProphecyTrait;

    private CompletenessRuleExampleFactory $factory;

    protected function setUp(): void
    {
        $ruleFactory = $this->prophesize(FactoryInterface::class);
        $ruleFactory->createNew()->will(static fn (): CompletenessRule => new CompletenessRule());

        $this->factory = new CompletenessRuleExampleFactory($ruleFactory->reveal());
    }

    /**
     * @test
     */
    public function it_creates_a_rule_from_options(): void
    {
        $rule = $this->factory->create([
            'label' => 'Has at least 3 images',
            'type' => 'has_minimum_images',
            'configuration' => ['count' => 3],
            'group' => 'Media',
            'weight_tier' => 'critical',
            'position' => 50,
        ]);

        self::assertSame('Has at least 3 images', $rule->getLabel());
        self::assertSame('has_at_least_3_images', $rule->getCode());
        self::assertSame('has_minimum_images', $rule->getType());
        self::assertSame(['count' => 3], $rule->getConfiguration());
        self::assertSame('Media', $rule->getGroup());
        self::assertSame('critical', $rule->getWeightTier());
        self::assertSame(50, $rule->getPosition());
        self::assertTrue($rule->isEnabled());
    }

    /**
     * @test
     */
    public function it_keeps_an_explicit_code(): void
    {
        $rule = $this->factory->create(['label' => 'Has a name', 'code' => 'custom_code']);

        self::assertSame('custom_code', $rule->getCode());
    }

    /**
     * @test
     */
    public function it_applies_sensible_defaults(): void
    {
        $rule = $this->factory->create(['label' => 'Product is enabled']);

        self::assertSame('is_enabled', $rule->getType());
        self::assertSame('medium', $rule->getWeightTier());
        self::assertSame([], $rule->getConfiguration());
        self::assertNull($rule->getGroup());
        self::assertNull($rule->getCustomWeight());
        self::assertTrue($rule->isEnabled());
        self::assertSame(0, $rule->getPosition());
    }

    /**
     * @test
     */
    public function it_creates_an_expression_rule(): void
    {
        $rule = $this->factory->create([
            'label' => 'Long description',
            'type' => 'expression',
            'configuration' => ['expression' => 'word_count(product.getDescription()) >= 100'],
        ]);

        self::assertSame('expression', $rule->getType());
        self::assertSame(['expression' => 'word_count(product.getDescription()) >= 100'], $rule->getConfiguration());
    }
}

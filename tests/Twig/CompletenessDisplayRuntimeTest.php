<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusCompletenessPlugin\Calculator\RuleWeightResolver;
use Setono\SyliusCompletenessPlugin\Display\ThresholdColor;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionFunctionNameProviderInterface;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRule;
use Setono\SyliusCompletenessPlugin\Repository\CompletenessRuleRepositoryInterface;
use Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManagerInterface;
use Setono\SyliusCompletenessPlugin\Tests\Fixture\CompletenessAwareProduct;
use Setono\SyliusCompletenessPlugin\Twig\CompletenessDisplayRuntime;
use Setono\SyliusCompletenessPlugin\ViewModel\CompletenessPanel;
use Setono\SyliusCompletenessPlugin\ViewModel\CompletenessPanelFactoryInterface;
use Sylius\Component\Core\Model\Product;

final class CompletenessDisplayRuntimeTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<CompletenessRuleRepositoryInterface> */
    private ObjectProphecy $ruleRepository;

    /** @var ObjectProphecy<RubricVersionManagerInterface> */
    private ObjectProphecy $rubricVersionManager;

    /** @var ObjectProphecy<CompletenessPanelFactoryInterface> */
    private ObjectProphecy $panelFactory;

    protected function setUp(): void
    {
        $this->ruleRepository = $this->prophesize(CompletenessRuleRepositoryInterface::class);
        $this->rubricVersionManager = $this->prophesize(RubricVersionManagerInterface::class);
        $this->panelFactory = $this->prophesize(CompletenessPanelFactoryInterface::class);
    }

    /**
     * @param array<string, string> $checkers
     */
    private function createRuntime(array $checkers = [], int $defaultThreshold = 80, int $amberBand = 20): CompletenessDisplayRuntime
    {
        $functionNameProvider = $this->prophesize(ExpressionFunctionNameProviderInterface::class);
        $functionNameProvider->getNames()->willReturn(['has_attribute', 'word_count']);

        return new CompletenessDisplayRuntime(
            $this->ruleRepository->reveal(),
            new RuleWeightResolver(['low' => 1.0, 'medium' => 3.0, 'high' => 6.0, 'critical' => 10.0]),
            $this->rubricVersionManager->reveal(),
            $this->panelFactory->reveal(),
            $functionNameProvider->reveal(),
            $checkers,
            $defaultThreshold,
            $amberBand,
        );
    }

    private function createRule(string $tier, bool $enabled = true): CompletenessRule
    {
        $rule = new CompletenessRule();
        $rule->setWeightTier($tier);
        $rule->setEnabled($enabled);

        return $rule;
    }

    /**
     * @test
     */
    public function it_computes_the_share_and_memoizes_the_total(): void
    {
        $critical = $this->createRule('critical');
        $medium = $this->createRule('medium');
        $low = $this->createRule('low');

        $this->ruleRepository->findEnabled()->willReturn([$critical, $medium, $low])->shouldBeCalledOnce();

        $runtime = $this->createRuntime();

        self::assertEqualsWithDelta(10 / 14, $runtime->ruleShare($critical), 0.0001);
        self::assertEqualsWithDelta(3 / 14, $runtime->ruleShare($medium), 0.0001);
        self::assertEqualsWithDelta(1 / 14, $runtime->ruleShare($low), 0.0001);
    }

    /**
     * @test
     */
    public function it_returns_zero_share_for_disabled_rules(): void
    {
        $this->ruleRepository->findEnabled()->willReturn([]);

        self::assertSame(0.0, $this->createRuntime()->ruleShare($this->createRule('medium', enabled: false)));
    }

    /**
     * @test
     */
    public function it_resolves_checker_labels_with_a_fallback(): void
    {
        $runtime = $this->createRuntime(['has_name' => 'setono_sylius_completeness.ui.checker.has_name']);

        self::assertSame('setono_sylius_completeness.ui.checker.has_name', $runtime->checkerLabel('has_name'));
        self::assertSame('custom_type', $runtime->checkerLabel('custom_type'));
    }

    /**
     * @test
     */
    public function it_resolves_the_threshold_color_with_the_default_threshold(): void
    {
        $runtime = $this->createRuntime(defaultThreshold: 80, amberBand: 20);

        self::assertSame(ThresholdColor::GREEN, $runtime->thresholdColor(85));
        self::assertSame(ThresholdColor::AMBER, $runtime->thresholdColor(70));
        self::assertSame(ThresholdColor::RED, $runtime->thresholdColor(10));
        self::assertSame(ThresholdColor::NA, $runtime->thresholdColor(null));
        // explicit per-context threshold override
        self::assertSame(ThresholdColor::GREEN, $runtime->thresholdColor(60, 50));
    }

    /**
     * @test
     */
    public function it_flags_a_stale_product_and_memoizes_the_current_version(): void
    {
        $this->rubricVersionManager->getCurrentVersion()->willReturn(5)->shouldBeCalledOnce();

        $runtime = $this->createRuntime();

        $stale = new CompletenessAwareProduct();
        $stale->setCompletenessRubricVersion(3);
        self::assertTrue($runtime->isStale($stale));

        $current = new CompletenessAwareProduct();
        $current->setCompletenessRubricVersion(5);
        self::assertFalse($runtime->isStale($current));
    }

    /**
     * @test
     */
    public function it_does_not_flag_a_never_calculated_product_as_stale(): void
    {
        $this->rubricVersionManager->getCurrentVersion()->shouldNotBeCalled();

        self::assertFalse($this->createRuntime()->isStale(new CompletenessAwareProduct()));
    }

    /**
     * @test
     */
    public function it_does_not_flag_a_non_completeness_aware_product_as_stale(): void
    {
        self::assertFalse($this->createRuntime()->isStale(new Product()));
    }

    /**
     * @test
     */
    public function it_delegates_panel_building_to_the_factory(): void
    {
        $product = new CompletenessAwareProduct();
        $panel = new CompletenessPanel([], [], [], null, 80, ThresholdColor::NA, false, null);

        $this->panelFactory->create($product)->willReturn($panel);

        self::assertSame($panel, $this->createRuntime()->panel($product));
    }
}

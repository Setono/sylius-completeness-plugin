<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\ViewModel;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusCompletenessPlugin\Display\ThresholdColor;
use Setono\SyliusCompletenessPlugin\Model\ProductCompleteness;
use Setono\SyliusCompletenessPlugin\Provider\ContextSettingsProviderInterface;
use Setono\SyliusCompletenessPlugin\Resolver\ThresholdResolverInterface;
use Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManagerInterface;
use Setono\SyliusCompletenessPlugin\Tests\Fixture\CompletenessAwareProduct;
use Setono\SyliusCompletenessPlugin\ViewModel\CompletenessPanelFactory;

final class CompletenessPanelFactoryTest extends TestCase
{
    use ProphecyTrait;

    private ThresholdResolverInterface $thresholdResolver;

    private ContextSettingsProviderInterface $contextSettings;

    private RubricVersionManagerInterface $rubricVersionManager;

    protected function setUp(): void
    {
        $thresholdResolver = $this->prophesize(ThresholdResolverInterface::class);
        $thresholdResolver->resolve(\Prophecy\Argument::cetera())->willReturn(80);
        $thresholdResolver->resolveDefault()->willReturn(80);
        $this->thresholdResolver = $thresholdResolver->reveal();

        $contextSettings = $this->prophesize(ContextSettingsProviderInterface::class);
        $contextSettings->getRollupWeight(\Prophecy\Argument::cetera())->willReturn(1.0);
        $this->contextSettings = $contextSettings->reveal();

        $rubricVersionManager = $this->prophesize(RubricVersionManagerInterface::class);
        $rubricVersionManager->getCurrentVersion()->willReturn(3);
        $this->rubricVersionManager = $rubricVersionManager->reveal();
    }

    private function createFactory(): CompletenessPanelFactory
    {
        return new CompletenessPanelFactory(
            $this->thresholdResolver,
            $this->contextSettings,
            $this->rubricVersionManager,
            20,
        );
    }

    private function createRow(string $channelCode, string $localeCode, ?int $ratio): ProductCompleteness
    {
        $row = new ProductCompleteness();
        $row->setChannelCode($channelCode);
        $row->setLocaleCode($localeCode);
        $row->setRatio($ratio);
        $row->setCalculatedAt(new \DateTimeImmutable('2026-07-02 10:00:00'));

        return $row;
    }

    /**
     * @test
     */
    public function it_builds_a_channel_locale_matrix(): void
    {
        $product = new CompletenessAwareProduct();
        $product->setCompletenessRatio(75);
        $product->addCompleteness($this->createRow('WEB', 'en', 100));
        $product->addCompleteness($this->createRow('WEB', 'da', 50));
        $product->addCompleteness($this->createRow('POS', 'en', 20));

        $panel = $this->createFactory()->create($product);

        self::assertSame(['WEB', 'POS'], $panel->channelCodes);
        self::assertSame(['en', 'da'], $panel->localeCodes);
        self::assertFalse($panel->isSingleContext());
        self::assertSame(75, $panel->globalRatio);
        self::assertSame(ThresholdColor::AMBER, $panel->globalColor);

        $webEn = $panel->getCell('WEB', 'en');
        self::assertNotNull($webEn);
        self::assertSame(100, $webEn->ratio);
        self::assertSame(ThresholdColor::GREEN, $webEn->color);

        $posEn = $panel->getCell('POS', 'en');
        self::assertNotNull($posEn);
        self::assertSame(ThresholdColor::RED, $posEn->color);

        self::assertNull($panel->getCell('POS', 'da'));
    }

    /**
     * @test
     */
    public function it_marks_a_single_context_product(): void
    {
        $product = new CompletenessAwareProduct();
        $product->addCompleteness($this->createRow('WEB', 'en', 90));

        $panel = $this->createFactory()->create($product);

        self::assertTrue($panel->isSingleContext());
        self::assertSame(90, $panel->getSingleCell()?->ratio);
    }

    /**
     * @test
     */
    public function it_renders_na_cells_without_a_ratio(): void
    {
        $product = new CompletenessAwareProduct();
        $product->addCompleteness($this->createRow('WEB', 'en', null));

        $cell = $this->createFactory()->create($product)->getSingleCell();

        self::assertNotNull($cell);
        self::assertTrue($cell->isNotApplicable());
        self::assertSame(ThresholdColor::NA, $cell->color);
    }

    /**
     * @test
     */
    public function it_marks_excluded_contexts(): void
    {
        $contextSettings = $this->prophesize(ContextSettingsProviderInterface::class);
        $contextSettings->getRollupWeight('WEB', 'en')->willReturn(0.0);
        $this->contextSettings = $contextSettings->reveal();

        $product = new CompletenessAwareProduct();
        $product->addCompleteness($this->createRow('WEB', 'en', 50));

        $cell = $this->createFactory()->create($product)->getSingleCell();

        self::assertNotNull($cell);
        self::assertTrue($cell->excluded);
    }

    /**
     * @test
     */
    public function it_orders_unmet_rules_by_weight_descending_within_each_group(): void
    {
        $row = $this->createRow('WEB', 'en', 40);
        $row->setUnmetRules([
            ['code' => 'a', 'label' => 'A', 'group' => 'Content', 'checkerType' => 'has_name', 'weight' => 3.0, 'score' => 0.0, 'errored' => false],
            ['code' => 'b', 'label' => 'B', 'group' => 'Content', 'checkerType' => 'has_description', 'weight' => 10.0, 'score' => 0.0, 'errored' => false],
            ['code' => 'c', 'label' => 'C', 'group' => 'Media', 'checkerType' => 'has_image', 'weight' => 6.0, 'score' => 0.0, 'errored' => false],
        ]);

        $product = new CompletenessAwareProduct();
        $product->addCompleteness($row);

        $cell = $this->createFactory()->create($product)->getSingleCell();
        self::assertNotNull($cell);

        $groups = $cell->unmetRuleGroups;
        self::assertCount(2, $groups);
        self::assertSame('Content', $groups[0]['group']);
        self::assertSame(['B', 'A'], array_map(static fn (array $r): string => $r['label'], $groups[0]['rules']));
    }

    /**
     * @test
     */
    public function it_flags_a_stale_product(): void
    {
        $product = new CompletenessAwareProduct();
        $product->setCompletenessRubricVersion(1); // current is 3
        $product->addCompleteness($this->createRow('WEB', 'en', 90));

        self::assertTrue($this->createFactory()->create($product)->stale);
    }

    /**
     * @test
     */
    public function it_computes_the_latest_calculation_time(): void
    {
        $older = $this->createRow('WEB', 'en', 90);
        $older->setCalculatedAt(new \DateTimeImmutable('2026-07-01 09:00:00'));

        $newer = $this->createRow('WEB', 'da', 90);
        $newer->setCalculatedAt(new \DateTimeImmutable('2026-07-02 15:00:00'));

        $product = new CompletenessAwareProduct();
        $product->addCompleteness($older);
        $product->addCompleteness($newer);

        self::assertSame('2026-07-02 15:00:00', $this->createFactory()->create($product)->lastCalculatedAt?->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
    public function it_returns_an_empty_panel_for_a_product_without_completeness(): void
    {
        self::assertFalse($this->createFactory()->create(new CompletenessAwareProduct())->hasData());
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Updater;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusCompletenessPlugin\Calculator\CompletenessCalculatorInterface;
use Setono\SyliusCompletenessPlugin\Calculator\Result\ContextResult;
use Setono\SyliusCompletenessPlugin\Calculator\Result\GroupScore;
use Setono\SyliusCompletenessPlugin\Calculator\Result\ProductCompletenessResult;
use Setono\SyliusCompletenessPlugin\Calculator\Result\RuleResult;
use Setono\SyliusCompletenessPlugin\Event\ProductCompletenessCalculated;
use Setono\SyliusCompletenessPlugin\Model\ProductCompleteness;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessAwareInterface;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessAwareTrait;
use Setono\SyliusCompletenessPlugin\Updater\ProductCompletenessUpdater;
use Sylius\Component\Core\Model\Product as BaseProduct;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class ProductCompletenessUpdaterTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<CompletenessCalculatorInterface> */
    private ObjectProphecy $calculator;

    /** @var ObjectProphecy<EntityManagerInterface> */
    private ObjectProphecy $manager;

    private EventDispatcher $eventDispatcher;

    /** @var list<object> */
    private array $dispatchedEvents = [];

    protected function setUp(): void
    {
        $this->calculator = $this->prophesize(CompletenessCalculatorInterface::class);
        $this->manager = $this->prophesize(EntityManagerInterface::class);

        $this->eventDispatcher = new EventDispatcher();
        $this->dispatchedEvents = [];
        $this->eventDispatcher->addListener(ProductCompletenessCalculated::class, function (object $event): void {
            $this->dispatchedEvents[] = $event;
        });
    }

    private function createUpdater(): ProductCompletenessUpdater
    {
        $factory = $this->prophesize(FactoryInterface::class);
        $factory->createNew()->will(static fn (): ProductCompleteness => new ProductCompleteness());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Argument::type('string'))->willReturn($this->manager->reveal());

        return new ProductCompletenessUpdater(
            $this->calculator->reveal(),
            $factory->reveal(),
            $managerRegistry->reveal(),
            $this->eventDispatcher,
        );
    }

    private function createProduct(): BaseProduct&ProductCompletenessAwareInterface
    {
        return new class() extends BaseProduct implements ProductCompletenessAwareInterface {
            use ProductCompletenessAwareTrait;
        };
    }

    private function createCompletenessResult(ContextResult ...$contextResults): ProductCompletenessResult
    {
        return new ProductCompletenessResult(
            globalRatio: 75,
            contextResults: array_values($contextResults),
            rubricVersion: 3,
            calculatedAt: new \DateTimeImmutable('2026-07-02 12:00:00'),
        );
    }

    private function createContextResult(string $channelCode, string $localeCode, ?int $ratio, RuleResult ...$ruleResults): ContextResult
    {
        return new ContextResult(
            channelCode: $channelCode,
            localeCode: $localeCode,
            ratio: $ratio,
            weightedPassed: 6.0,
            weightedTotal: 8.0,
            groupScores: [new GroupScore('Media', $ratio, 6.0, 8.0)],
            ruleResults: array_values($ruleResults),
            rollupWeight: 1.0,
            excluded: false,
        );
    }

    /**
     * @test
     */
    public function it_throws_for_a_product_that_is_not_completeness_aware(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createUpdater()->update(new BaseProduct());
    }

    /**
     * @test
     */
    public function it_creates_rows_and_writes_the_rollup_onto_the_product(): void
    {
        $product = $this->createProduct();

        $met = new RuleResult('has_name', 'Has name', null, 'has_name', 10.0, 1.0, false);
        $unmet = new RuleResult('has_image', 'Has image', 'Media', 'has_image', 1.0, 0.0, false);
        $errored = new RuleResult('broken', 'Broken', 'Media', 'has_attribute', 3.0, 0.0, true, 'Something broke');

        $this->calculator->calculate($product)->willReturn($this->createCompletenessResult(
            $this->createContextResult('WEB', 'en', 75, $met, $unmet, $errored),
        ));
        $this->manager->flush()->shouldBeCalled();

        $this->createUpdater()->update($product);

        self::assertSame(75, $product->getCompletenessRatio());
        self::assertSame(3, $product->getCompletenessRubricVersion());
        self::assertCount(1, $product->getCompletenesses());

        /** @var ProductCompleteness $row */
        $row = $product->getCompletenesses()->first();
        self::assertSame('WEB', $row->getChannelCode());
        self::assertSame('en', $row->getLocaleCode());
        self::assertSame(75, $row->getRatio());
        self::assertSame(6.0, $row->getWeightedPassed());
        self::assertSame(8.0, $row->getWeightedTotal());
        self::assertSame('2026-07-02 12:00:00', $row->getCalculatedAt()?->format('Y-m-d H:i:s'));
        self::assertSame([
            ['group' => 'Media', 'ratio' => 75, 'weightedPassed' => 6.0, 'weightedTotal' => 8.0],
        ], $row->getGroupScores());
        // only rules scoring < 1 or errored are persisted, and errored rules carry their message
        self::assertSame([
            ['code' => 'has_image', 'label' => 'Has image', 'group' => 'Media', 'checkerType' => 'has_image', 'weight' => 1.0, 'score' => 0.0, 'errored' => false],
            ['code' => 'broken', 'label' => 'Broken', 'group' => 'Media', 'checkerType' => 'has_attribute', 'weight' => 3.0, 'score' => 0.0, 'errored' => true, 'error' => 'Something broke'],
        ], $row->getUnmetRules());
    }

    /**
     * @test
     */
    public function it_reuses_existing_rows_and_prunes_stale_ones(): void
    {
        $product = $this->createProduct();

        $existingRow = new ProductCompleteness();
        $existingRow->setChannelCode('WEB');
        $existingRow->setLocaleCode('en');
        $existingRow->setRatio(10);
        $product->addCompleteness($existingRow);

        $staleRow = new ProductCompleteness();
        $staleRow->setChannelCode('POS');
        $staleRow->setLocaleCode('en');
        $product->addCompleteness($staleRow);

        $this->calculator->calculate($product)->willReturn($this->createCompletenessResult(
            $this->createContextResult('WEB', 'en', 80),
        ));
        $this->manager->flush()->shouldBeCalled();

        $this->createUpdater()->update($product);

        self::assertCount(1, $product->getCompletenesses());
        self::assertTrue($product->hasCompleteness($existingRow));
        self::assertSame(80, $existingRow->getRatio());
        self::assertFalse($product->hasCompleteness($staleRow));
    }

    /**
     * @test
     */
    public function it_persists_na_rows(): void
    {
        $product = $this->createProduct();

        $this->calculator->calculate($product)->willReturn(new ProductCompletenessResult(
            globalRatio: null,
            contextResults: [$this->createContextResult('WEB', 'en', null)],
            rubricVersion: 3,
            calculatedAt: new \DateTimeImmutable(),
        ));
        $this->manager->flush()->shouldBeCalled();

        $this->createUpdater()->update($product);

        self::assertNull($product->getCompletenessRatio());
        self::assertCount(1, $product->getCompletenesses());

        /** @var ProductCompleteness $row */
        $row = $product->getCompletenesses()->first();
        self::assertNull($row->getRatio());
    }

    /**
     * @test
     */
    public function it_dispatches_the_calculated_event_with_the_bulk_flag(): void
    {
        $product = $this->createProduct();

        $result = $this->createCompletenessResult($this->createContextResult('WEB', 'en', 75));
        $this->calculator->calculate($product)->willReturn($result);
        $this->manager->flush()->shouldBeCalled();

        $this->createUpdater()->update($product, bulk: true);

        self::assertCount(1, $this->dispatchedEvents);

        $event = $this->dispatchedEvents[0];
        self::assertInstanceOf(ProductCompletenessCalculated::class, $event);
        self::assertSame($product, $event->product);
        self::assertSame($result, $event->result);
        self::assertTrue($event->bulk);
    }
}

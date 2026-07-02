<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusCompletenessPlugin\Calculator\Result\ProductCompletenessResult;
use Setono\SyliusCompletenessPlugin\Command\RecalculateCommand;
use Setono\SyliusCompletenessPlugin\Provider\ProductIdsProviderInterface;
use Setono\SyliusCompletenessPlugin\Provider\ProductProviderInterface;
use Setono\SyliusCompletenessPlugin\Updater\ProductCompletenessUpdaterInterface;
use Sylius\Component\Core\Model\Product;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class RecalculateCommandTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<ProductIdsProviderInterface> */
    private ObjectProphecy $productIdsProvider;

    /** @var ObjectProphecy<ProductProviderInterface> */
    private ObjectProphecy $productProvider;

    /** @var ObjectProphecy<ProductCompletenessUpdaterInterface> */
    private ObjectProphecy $updater;

    protected function setUp(): void
    {
        $this->productIdsProvider = $this->prophesize(ProductIdsProviderInterface::class);
        $this->productProvider = $this->prophesize(ProductProviderInterface::class);
        $this->updater = $this->prophesize(ProductCompletenessUpdaterInterface::class);
    }

    private function createTester(): CommandTester
    {
        $manager = $this->prophesize(EntityManagerInterface::class);

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Product::class)->willReturn($manager->reveal());

        return new CommandTester(new RecalculateCommand(
            $this->productIdsProvider->reveal(),
            $this->productProvider->reveal(),
            $this->updater->reveal(),
            $managerRegistry->reveal(),
            Product::class,
        ));
    }

    /**
     * @test
     */
    public function it_recalculates_selected_products_synchronously(): void
    {
        $product = new Product();

        $this->productIdsProvider->getChunks(100, ['SHIRT'])->willReturn([[5]]);
        $this->productProvider->findByIds([5])->willReturn([$product]);
        $this->updater->update($product, false)->willReturn(new ProductCompletenessResult(null, [], 0, new \DateTimeImmutable()))->shouldBeCalled();

        $tester = $this->createTester();
        $exitCode = $tester->execute(['--product' => ['SHIRT']]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Recalculated the completeness of 1 product(s)', $tester->getDisplay());
    }

    /**
     * @test
     */
    public function it_recalculates_the_whole_catalog_with_the_bulk_flag(): void
    {
        $product = new Product();

        $this->productIdsProvider->getChunks(100, null)->willReturn([[1]]);
        $this->productProvider->findByIds([1])->willReturn([$product]);
        $this->updater->update($product, true)->willReturn(new ProductCompletenessResult(null, [], 0, new \DateTimeImmutable()))->shouldBeCalled();

        self::assertSame(Command::SUCCESS, $this->createTester()->execute(['--all' => true]));
    }

    /**
     * @test
     */
    public function it_fails_without_a_mode(): void
    {
        $this->updater->update(Argument::cetera())->shouldNotBeCalled();

        $tester = $this->createTester();

        self::assertSame(Command::INVALID, $tester->execute([]));
        self::assertStringContainsString('either --all or at least one --product', $tester->getDisplay());
    }

    /**
     * @test
     */
    public function it_fails_with_both_modes(): void
    {
        self::assertSame(Command::INVALID, $this->createTester()->execute(['--all' => true, '--product' => ['SHIRT']]));
    }
}

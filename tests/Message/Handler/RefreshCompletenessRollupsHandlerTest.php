<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Message\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusCompletenessPlugin\Message\Command\RefreshCompletenessRollups;
use Setono\SyliusCompletenessPlugin\Message\Handler\RefreshCompletenessRollupsHandler;
use Setono\SyliusCompletenessPlugin\Provider\CompletenessContextProviderInterface;
use Setono\SyliusCompletenessPlugin\Provider\ProductIdsProviderInterface;
use Setono\SyliusCompletenessPlugin\Provider\ProductProviderInterface;
use Setono\SyliusCompletenessPlugin\Repository\ProductCompletenessRepositoryInterface;
use Setono\SyliusCompletenessPlugin\Rollup\Rollup;
use Setono\SyliusCompletenessPlugin\Rollup\WeightedAverageRollupStrategy;
use Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManagerInterface;
use Setono\SyliusCompletenessPlugin\Tests\Fixture\CompletenessAwareProduct;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class RefreshCompletenessRollupsHandlerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_recomputes_the_global_ratio_from_existing_rows_and_stamps_the_version(): void
    {
        $product = new CompletenessAwareProduct();
        $product->setId(1);
        $product->setCompletenessRatio(10);
        $product->setCompletenessRubricVersion(1);

        $productIdsProvider = $this->prophesize(ProductIdsProviderInterface::class);
        $productIdsProvider->getChunks(100)->willReturn([[1]]);

        $productProvider = $this->prophesize(ProductProviderInterface::class);
        $productProvider->findByIds([1])->willReturn([$product]);

        $completenessRepository = $this->prophesize(ProductCompletenessRepositoryInterface::class);
        $completenessRepository->findRatiosGroupedByProduct([1])->willReturn([
            1 => [
                ['channelCode' => 'WEB', 'localeCode' => 'en', 'ratio' => 100],
                ['channelCode' => 'WEB', 'localeCode' => 'da', 'ratio' => 0],
                ['channelCode' => 'POS', 'localeCode' => 'en', 'ratio' => null], // N/A
            ],
        ]);

        // the da context is excluded via a rollup weight of 0
        $contexts = $this->prophesize(CompletenessContextProviderInterface::class);
        $contexts->getRollupWeight('WEB', 'en')->willReturn(1.0);
        $contexts->getRollupWeight('WEB', 'da')->willReturn(0.0);
        $contexts->getRollupWeight('POS', 'en')->willReturn(1.0);

        $rubricVersionManager = $this->prophesize(RubricVersionManagerInterface::class);
        $rubricVersionManager->getCurrentVersion()->willReturn(9);

        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->flush()->shouldBeCalled();
        $manager->clear()->shouldBeCalled();

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(CompletenessAwareProduct::class)->willReturn($manager->reveal());

        $handler = new RefreshCompletenessRollupsHandler(
            $productIdsProvider->reveal(),
            $productProvider->reveal(),
            $completenessRepository->reveal(),
            $contexts->reveal(),
            new Rollup(
                new ServiceLocator(['weighted_average' => static fn (): WeightedAverageRollupStrategy => new WeightedAverageRollupStrategy()]),
                'weighted_average',
            ),
            $rubricVersionManager->reveal(),
            $managerRegistry->reveal(),
            CompletenessAwareProduct::class,
        );

        $handler(new RefreshCompletenessRollups());

        self::assertSame(100, $product->getCompletenessRatio());
        self::assertSame(9, $product->getCompletenessRubricVersion());
    }
}

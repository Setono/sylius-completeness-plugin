<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Message\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusCompletenessPlugin\Calculator\Result\ProductCompletenessResult;
use Setono\SyliusCompletenessPlugin\Message\Command\RecalculateAllProductsCompleteness;
use Setono\SyliusCompletenessPlugin\Message\Handler\RecalculateAllProductsCompletenessHandler;
use Setono\SyliusCompletenessPlugin\Provider\ProductIdsProviderInterface;
use Setono\SyliusCompletenessPlugin\Provider\ProductProviderInterface;
use Setono\SyliusCompletenessPlugin\Updater\ProductCompletenessUpdaterInterface;
use Sylius\Component\Core\Model\Product;

final class RecalculateAllProductsCompletenessHandlerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_updates_all_products_in_chunks_and_clears_the_manager_between_chunks(): void
    {
        $firstChunkProducts = [new Product(), new Product()];
        $secondChunkProducts = [new Product()];

        $productIdsProvider = $this->prophesize(ProductIdsProviderInterface::class);
        $productIdsProvider->getChunks(100)->willReturn([[1, 2], [3]]);

        $productProvider = $this->prophesize(ProductProviderInterface::class);
        $productProvider->findByIds([1, 2])->willReturn($firstChunkProducts);
        $productProvider->findByIds([3])->willReturn($secondChunkProducts);

        $updater = $this->prophesize(ProductCompletenessUpdaterInterface::class);
        foreach ([...$firstChunkProducts, ...$secondChunkProducts] as $product) {
            $updater->update($product, true)->willReturn(new ProductCompletenessResult(null, [], 0, new \DateTimeImmutable()))->shouldBeCalled();
        }

        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->clear()->shouldBeCalledTimes(2);

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Product::class)->willReturn($manager->reveal());

        $handler = new RecalculateAllProductsCompletenessHandler(
            $productIdsProvider->reveal(),
            $productProvider->reveal(),
            $updater->reveal(),
            $managerRegistry->reveal(),
            Product::class,
        );

        $handler(new RecalculateAllProductsCompleteness());
    }
}

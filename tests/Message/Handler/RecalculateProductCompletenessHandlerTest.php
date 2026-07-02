<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Message\Handler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusCompletenessPlugin\Calculator\Result\ProductCompletenessResult;
use Setono\SyliusCompletenessPlugin\Message\Command\RecalculateProductCompleteness;
use Setono\SyliusCompletenessPlugin\Message\Handler\RecalculateProductCompletenessHandler;
use Setono\SyliusCompletenessPlugin\Provider\ProductProviderInterface;
use Setono\SyliusCompletenessPlugin\Updater\ProductCompletenessUpdaterInterface;
use Sylius\Component\Core\Model\Product;

final class RecalculateProductCompletenessHandlerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_updates_the_product_and_propagates_the_bulk_flag(): void
    {
        $product = new Product();

        $productProvider = $this->prophesize(ProductProviderInterface::class);
        $productProvider->findById(42)->willReturn($product);

        $updater = $this->prophesize(ProductCompletenessUpdaterInterface::class);
        $updater->update($product, true)->willReturn(new ProductCompletenessResult(null, [], 0, new \DateTimeImmutable()))->shouldBeCalled();

        (new RecalculateProductCompletenessHandler($productProvider->reveal(), $updater->reveal()))(
            new RecalculateProductCompleteness(42, bulk: true),
        );
    }

    /**
     * @test
     */
    public function it_does_nothing_for_a_deleted_product(): void
    {
        $productProvider = $this->prophesize(ProductProviderInterface::class);
        $productProvider->findById(42)->willReturn(null);

        $updater = $this->prophesize(ProductCompletenessUpdaterInterface::class);
        $updater->update(Argument::cetera())->shouldNotBeCalled();

        (new RecalculateProductCompletenessHandler($productProvider->reveal(), $updater->reveal()))(
            new RecalculateProductCompleteness(42),
        );
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Doctrine\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\AffectedProductsProvider;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\AffectedProductsResolverInterface;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\ProductImageResolver;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\ProductResolver;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductImage;
use Sylius\Component\Core\Model\ProductInterface;

final class AffectedProductsProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @param list<AffectedProductsResolverInterface> $resolvers
     */
    private function createProvider(array $resolvers, ?string $resolvedClass = null): AffectedProductsProvider
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->getClassMetadata(Argument::type('string'))->will(
            static function (array $args) use ($resolvedClass): ClassMetadata {
                $class = $resolvedClass ?? $args[0];
                if (!is_string($class) || (!class_exists($class) && !interface_exists($class))) {
                    throw new \InvalidArgumentException('Expected a class name');
                }

                return new ClassMetadata($class);
            },
        );

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Argument::type('string'))->willReturn($entityManager->reveal());

        return new AffectedProductsProvider($resolvers, $managerRegistry->reveal());
    }

    /**
     * @test
     */
    public function it_resolves_a_product_to_itself(): void
    {
        $product = new Product();

        $products = [...$this->createProvider([new ProductResolver()])->getProducts($product)];

        self::assertSame([$product], $products);
    }

    /**
     * @test
     */
    public function it_resolves_a_child_entity_to_its_owning_product(): void
    {
        $product = new Product();
        $image = new ProductImage();
        $image->setOwner($product);

        $products = [...$this->createProvider([new ProductImageResolver()])->getProducts($image)];

        self::assertSame([$product], $products);
    }

    /**
     * @test
     */
    public function it_resolves_a_doctrine_proxy_through_the_metadata(): void
    {
        // a proxy reports its own class; the metadata resolves it to the real product class
        $proxy = new class() extends Product {
        };

        $products = [...$this->createProvider([new ProductResolver()], resolvedClass: Product::class)->getProducts($proxy)];

        self::assertSame([$proxy], $products);
    }

    /**
     * @test
     */
    public function it_returns_nothing_for_an_unrelated_entity(): void
    {
        $products = [...$this->createProvider([new ProductResolver()])->getProducts(new \stdClass())];

        self::assertSame([], $products);
    }

    /**
     * @test
     */
    public function it_supports_custom_resolvers_and_lets_the_last_one_win(): void
    {
        $product = new Product();
        $wishlist = new TestWishlist($product);

        $resolver = new class() implements AffectedProductsResolverInterface {
            public function getSupportedClasses(): array
            {
                return [TestWishlist::class];
            }

            public function getProducts(object $entity): iterable
            {
                if ($entity instanceof TestWishlist) {
                    yield $entity->product;
                }
            }
        };

        $products = [...$this->createProvider([new ProductResolver(), $resolver])->getProducts($wishlist)];

        self::assertSame([$product], $products);
    }
}

/**
 * A host entity used to prove the resolver extension point
 */
final class TestWishlist
{
    public function __construct(public readonly ProductInterface $product)
    {
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\EventListener\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\AffectedProductsResolverInterface;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\ProductImageResolver;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\ProductResolver;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\ProductTranslationResolver;
use Setono\SyliusCompletenessPlugin\EventListener\Doctrine\RecalculateAffectedProductsListener;
use Setono\SyliusCompletenessPlugin\Message\Command\RecalculateAllProductsCompleteness;
use Setono\SyliusCompletenessPlugin\Message\Command\RecalculateProductCompleteness;
use Setono\SyliusCompletenessPlugin\Model\ProductCompleteness;
use Setono\SyliusCompletenessPlugin\Tests\Fixture\CompletenessAwareProduct;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductImage;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductTranslation;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

final class RecalculateAffectedProductsListenerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<EntityManagerInterface> */
    private ObjectProphecy $entityManager;

    private FakeUnitOfWork $unitOfWork;

    /** @var ObjectProphecy<MessageBusInterface> */
    private ObjectProphecy $commandBus;

    /** @var ObjectProphecy<LoggerInterface> */
    private ObjectProphecy $logger;

    protected function setUp(): void
    {
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->entityManager->getEventManager()->willReturn(new EventManager());
        $this->entityManager->getConfiguration()->willReturn(new Configuration());
        $this->entityManager->getMetadataFactory()->willReturn($this->prophesize(ClassMetadataFactory::class)->reveal());

        $this->unitOfWork = new FakeUnitOfWork($this->entityManager->reveal());

        $this->entityManager->getUnitOfWork()->willReturn($this->unitOfWork);
        $this->entityManager->getClassMetadata(Argument::type('string'))->will(
            static function (array $args): ClassMetadata {
                $class = $args[0];
                if (!is_string($class) || (!class_exists($class) && !interface_exists($class))) {
                    throw new \InvalidArgumentException('Expected a class name');
                }

                return new ClassMetadata($class);
            },
        );

        $this->commandBus = $this->prophesize(MessageBusInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
    }

    /**
     * @param list<AffectedProductsResolverInterface> $extraResolvers
     */
    private function createListener(
        int $bulkThreshold = 300,
        bool $enabled = true,
        array $extraResolvers = [],
    ): RecalculateAffectedProductsListener {
        return new RecalculateAffectedProductsListener(
            [
                ...$extraResolvers,
                new ProductResolver(),
                new ProductTranslationResolver(),
                new ProductImageResolver(),
            ],
            $this->commandBus->reveal(),
            $this->logger->reveal(),
            $bulkThreshold,
            $enabled,
        );
    }

    private function flush(RecalculateAffectedProductsListener $listener): void
    {
        $listener->onFlush(new OnFlushEventArgs($this->entityManager->reveal()));
        $listener->postFlush(new PostFlushEventArgs($this->entityManager->reveal()));
    }

    private function createProduct(int $id): CompletenessAwareProduct
    {
        $product = new CompletenessAwareProduct();
        $product->setId($id);

        return $product;
    }

    /**
     * @param class-string $elementClass
     *
     * @return PersistentCollection<array-key, object>
     */
    private function createCollection(string $elementClass, object $owner): PersistentCollection
    {
        $collection = new PersistentCollection(
            $this->entityManager->reveal(),
            new ClassMetadata($elementClass),
            new ArrayCollection(),
        );
        $collection->setOwner($owner, [
            'fieldName' => 'items',
            'inversedBy' => null,
            'mappedBy' => 'product',
            'cascade' => [],
            'fetch' => ClassMetadata::FETCH_LAZY,
            'isCascadeRemove' => false,
            'isCascadePersist' => false,
            'isCascadeRefresh' => false,
            'isCascadeMerge' => false,
            'isCascadeDetach' => false,
            'isOwningSide' => false,
            'sourceEntity' => $owner::class,
            'targetEntity' => $elementClass,
            'type' => ClassMetadata::ONE_TO_MANY,
        ]);

        return $collection;
    }

    private function expectSingleProductDispatch(int $productId): void
    {
        $this->commandBus->dispatch(Argument::that(
            static function (Envelope $envelope) use ($productId): bool {
                $message = $envelope->getMessage();

                return $message instanceof RecalculateProductCompleteness &&
                    $message->productId === $productId &&
                    [] !== $envelope->all(DispatchAfterCurrentBusStamp::class);
            },
        ))->willReturnArgument(0)->shouldBeCalledTimes(1);
    }

    /**
     * @test
     */
    public function it_dispatches_one_recalculation_for_a_product_and_its_children(): void
    {
        $product = $this->createProduct(42);

        $translation = new ProductTranslation();
        $translation->setTranslatable($product);

        $image = new ProductImage();
        $image->setOwner($product);

        $this->unitOfWork->scheduledUpdates = [$product, $translation];
        $this->unitOfWork->scheduledInsertions = [$image];
        $this->unitOfWork->setChangeSet($product, ['code' => [null, 'shirt']]);

        $this->expectSingleProductDispatch(42);

        $this->flush($this->createListener());
    }

    /**
     * @test
     */
    public function it_resolves_doctrine_proxy_classes_through_the_metadata(): void
    {
        $product = $this->createProduct(7);

        // simulate a proxy: the metadata for the runtime class resolves to the real product class
        $this->entityManager->getClassMetadata($product::class)->willReturn(new ClassMetadata(Product::class));

        $this->unitOfWork->scheduledDeletions = [$product];

        $this->expectSingleProductDispatch(7);

        $this->flush($this->createListener());
    }

    /**
     * @test
     */
    public function it_ignores_product_updates_that_only_touch_the_completeness_fields(): void
    {
        $product = $this->createProduct(42);

        $this->unitOfWork->scheduledUpdates = [$product];
        $this->unitOfWork->setChangeSet($product, [
            'completenessRatio' => [null, 80],
            'completenessRubricVersion' => [null, 1],
        ]);

        $this->commandBus->dispatch(Argument::cetera())->shouldNotBeCalled();

        $this->flush($this->createListener());
    }

    /**
     * @test
     */
    public function it_still_recalculates_when_a_completeness_field_changes_alongside_other_fields(): void
    {
        $product = $this->createProduct(42);

        $this->unitOfWork->scheduledUpdates = [$product];
        $this->unitOfWork->setChangeSet($product, [
            'completenessRatio' => [null, 80],
            'code' => ['a', 'b'],
        ]);

        $this->expectSingleProductDispatch(42);

        $this->flush($this->createListener());
    }

    /**
     * @test
     */
    public function it_collects_owners_of_scheduled_collection_changes(): void
    {
        $product = $this->createProduct(11);

        $this->unitOfWork->scheduledCollectionUpdates = [$this->createCollection(ProductImage::class, $product)];

        $this->expectSingleProductDispatch(11);

        $this->flush($this->createListener());
    }

    /**
     * @test
     */
    public function it_skips_collections_of_completeness_rows(): void
    {
        $product = $this->createProduct(11);

        $this->unitOfWork->scheduledCollectionUpdates = [$this->createCollection(ProductCompleteness::class, $product)];

        $this->commandBus->dispatch(Argument::cetera())->shouldNotBeCalled();

        $this->flush($this->createListener());
    }

    /**
     * @test
     */
    public function it_ignores_unrelated_entities(): void
    {
        $this->unitOfWork->scheduledInsertions = [new \stdClass()];

        $this->commandBus->dispatch(Argument::cetera())->shouldNotBeCalled();

        $this->flush($this->createListener());
    }

    /**
     * @test
     */
    public function it_defers_to_a_single_bulk_recalculation_above_the_threshold(): void
    {
        $this->unitOfWork->scheduledInsertions = [$this->createProduct(1), $this->createProduct(2), $this->createProduct(3)];

        $this->commandBus->dispatch(Argument::that(
            static fn (Envelope $envelope): bool => $envelope->getMessage() instanceof RecalculateAllProductsCompleteness,
        ))->willReturnArgument(0)->shouldBeCalledTimes(1);
        $this->logger->warning(Argument::type('string'))->shouldBeCalled();

        $this->flush($this->createListener(bulkThreshold: 2));
    }

    /**
     * @test
     */
    public function it_does_nothing_when_disabled(): void
    {
        $this->unitOfWork->scheduledInsertions = [$this->createProduct(42)];

        $this->commandBus->dispatch(Argument::cetera())->shouldNotBeCalled();

        $this->flush($this->createListener(enabled: false));
    }

    /**
     * @test
     */
    public function it_supports_custom_resolvers_for_host_entities(): void
    {
        $product = $this->createProduct(99);
        $wishlist = new TestWishlist($product);

        $customResolver = new class() implements AffectedProductsResolverInterface {
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

        $this->unitOfWork->scheduledUpdates = [$wishlist];

        $this->expectSingleProductDispatch(99);

        $this->flush($this->createListener(extraResolvers: [$customResolver]));
    }
}

/**
 * Prophecy cannot double the UnitOfWork (getEntityChangeSet returns by reference),
 * so the schedule accessors are faked by hand
 */
final class FakeUnitOfWork extends UnitOfWork
{
    /** @var list<object> */
    public array $scheduledInsertions = [];

    /** @var list<object> */
    public array $scheduledUpdates = [];

    /** @var list<object> */
    public array $scheduledDeletions = [];

    /** @var list<PersistentCollection<array-key, object>> */
    public array $scheduledCollectionUpdates = [];

    /** @var list<PersistentCollection<array-key, object>> */
    public array $scheduledCollectionDeletions = [];

    /** @var array<int, array<string, array{mixed, mixed}>> */
    private array $changeSets = [];

    public function getScheduledEntityInsertions(): array
    {
        return $this->scheduledInsertions;
    }

    public function getScheduledEntityUpdates(): array
    {
        return $this->scheduledUpdates;
    }

    public function getScheduledEntityDeletions(): array
    {
        return $this->scheduledDeletions;
    }

    public function getScheduledCollectionUpdates(): array
    {
        return $this->scheduledCollectionUpdates;
    }

    public function getScheduledCollectionDeletions(): array
    {
        return $this->scheduledCollectionDeletions;
    }

    /**
     * @param array<string, array{mixed, mixed}> $changeSet
     */
    public function setChangeSet(object $entity, array $changeSet): void
    {
        $this->changeSets[spl_object_id($entity)] = $changeSet;
    }

    /**
     * @return array<string, array{mixed, mixed}>
     */
    public function &getEntityChangeSet($entity): array
    {
        $key = spl_object_id($entity);
        if (!isset($this->changeSets[$key])) {
            $this->changeSets[$key] = [];
        }

        return $this->changeSets[$key];
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

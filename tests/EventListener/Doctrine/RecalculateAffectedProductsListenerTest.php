<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\EventListener\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\AffectedProductsProviderInterface;
use Setono\SyliusCompletenessPlugin\EventListener\Doctrine\RecalculateAffectedProductsListener;
use Setono\SyliusCompletenessPlugin\Model\ProductCompleteness;
use Setono\SyliusCompletenessPlugin\Tests\Fixture\CompletenessAwareProduct;
use Sylius\Component\Core\Model\ProductImage;
use Symfony\Component\Clock\MockClock;

final class RecalculateAffectedProductsListenerTest extends TestCase
{
    use ProphecyTrait;

    private const NOW = '2026-01-02 03:04:05';

    /** @var ObjectProphecy<EntityManagerInterface> */
    private ObjectProphecy $entityManager;

    private FakeUnitOfWork $unitOfWork;

    /** @var ObjectProphecy<AffectedProductsProviderInterface> */
    private ObjectProphecy $affectedProductsProvider;

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

        $this->affectedProductsProvider = $this->prophesize(AffectedProductsProviderInterface::class);
        // by default a completeness-aware product resolves to itself; anything else resolves to nothing
        $this->affectedProductsProvider->getProducts(Argument::any())->will(
            static fn (array $args): array => $args[0] instanceof CompletenessAwareProduct ? [$args[0]] : [],
        );
    }

    private function createListener(bool $enabled = true): RecalculateAffectedProductsListener
    {
        return new RecalculateAffectedProductsListener(
            $this->affectedProductsProvider->reveal(),
            new MockClock(new \DateTimeImmutable(self::NOW)),
            $enabled,
        );
    }

    private function flush(RecalculateAffectedProductsListener $listener): void
    {
        $listener->onFlush(new OnFlushEventArgs($this->entityManager->reveal()));
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

    /**
     * @test
     */
    public function it_marks_an_updated_product_dirty_and_recomputes_its_changeset(): void
    {
        $product = $this->createProduct(42);
        $this->unitOfWork->scheduledUpdates = [$product];
        $this->unitOfWork->setChangeSet($product, ['code' => [null, 'shirt']]);

        $this->flush($this->createListener());

        self::assertEquals(new \DateTimeImmutable(self::NOW), $product->getCompletenessDirtyAt());
        self::assertSame([$product], $this->unitOfWork->recomputed);
        self::assertSame([], $this->unitOfWork->computed);
    }

    /**
     * @test
     */
    public function it_skips_new_products_since_a_null_rubric_version_already_makes_them_candidates(): void
    {
        $product = $this->createProduct(1);
        $this->unitOfWork->scheduledInsertions = [$product];

        $this->flush($this->createListener());

        self::assertNull($product->getCompletenessDirtyAt());
        self::assertSame([], $this->unitOfWork->recomputed);
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
            'completenessDirtyAt' => [new \DateTimeImmutable('2020-01-01'), null],
        ]);

        $this->flush($this->createListener());

        self::assertNull($product->getCompletenessDirtyAt());
        self::assertSame([], $this->unitOfWork->recomputed);
    }

    /**
     * @test
     */
    public function it_still_marks_dirty_when_a_completeness_field_changes_alongside_other_fields(): void
    {
        $product = $this->createProduct(42);
        $this->unitOfWork->scheduledUpdates = [$product];
        $this->unitOfWork->setChangeSet($product, [
            'completenessRatio' => [null, 80],
            'code' => ['a', 'b'],
        ]);

        $this->flush($this->createListener());

        self::assertEquals(new \DateTimeImmutable(self::NOW), $product->getCompletenessDirtyAt());
    }

    /**
     * @test
     */
    public function it_collects_owners_of_scheduled_collection_changes(): void
    {
        $product = $this->createProduct(11);
        $this->unitOfWork->scheduledCollectionUpdates = [$this->createCollection(ProductImage::class, $product)];

        $this->flush($this->createListener());

        self::assertEquals(new \DateTimeImmutable(self::NOW), $product->getCompletenessDirtyAt());
    }

    /**
     * @test
     */
    public function it_skips_collections_of_completeness_rows(): void
    {
        $product = $this->createProduct(11);
        $this->unitOfWork->scheduledCollectionUpdates = [$this->createCollection(ProductCompleteness::class, $product)];

        $this->flush($this->createListener());

        self::assertNull($product->getCompletenessDirtyAt());
    }

    /**
     * @test
     */
    public function it_dedupes_a_product_affected_through_several_changes(): void
    {
        $product = $this->createProduct(11);
        $this->unitOfWork->scheduledUpdates = [$product];
        $this->unitOfWork->setChangeSet($product, ['code' => ['a', 'b']]);
        $this->unitOfWork->scheduledCollectionUpdates = [$this->createCollection(ProductImage::class, $product)];

        $this->flush($this->createListener());

        self::assertSame([$product], $this->unitOfWork->recomputed);
    }

    /**
     * @test
     */
    public function it_does_nothing_when_disabled(): void
    {
        $product = $this->createProduct(42);
        $this->unitOfWork->scheduledInsertions = [$product];

        $this->flush($this->createListener(enabled: false));

        self::assertNull($product->getCompletenessDirtyAt());
        self::assertSame([], $this->unitOfWork->computed);
    }
}

/**
 * Prophecy cannot double the UnitOfWork (getEntityChangeSet returns by reference), so the schedule
 * accessors and the changeset (re)computation are faked by hand and only recorded here
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

    /** @var list<object> */
    public array $computed = [];

    /** @var list<object> */
    public array $recomputed = [];

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

    public function isScheduledForInsert($entity): bool
    {
        return in_array($entity, $this->scheduledInsertions, true);
    }

    public function computeChangeSet(ClassMetadata $class, $entity): void
    {
        $this->computed[] = $entity;
    }

    public function recomputeSingleEntityChangeSet(ClassMetadata $class, $entity): void
    {
        $this->recomputed[] = $entity;
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

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Psr\Log\LoggerInterface;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\AffectedProductsResolverInterface;
use Setono\SyliusCompletenessPlugin\Message\Command\RecalculateAllProductsCompleteness;
use Setono\SyliusCompletenessPlugin\Message\Command\RecalculateProductCompleteness;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessAwareInterface;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

/**
 * The single capture point of the recalculation pipeline: watches every Doctrine flush for
 * inserts, updates, deletes AND scheduled collection changes of entities that affect a product's
 * completeness. The set of watched classes is derived from the registered
 * AffectedProductsResolverInterface services - there is no config list to keep in sync
 */
final class RecalculateAffectedProductsListener
{
    /** @var array<class-string, AffectedProductsResolverInterface|null>|null lazily built class => resolver map (null = known non-match) */
    private ?array $resolverMap = null;

    /** @var array<int, ProductInterface> affected products, deduplicated by object id */
    private array $buffer = [];

    /**
     * @param iterable<AffectedProductsResolverInterface> $resolvers
     */
    public function __construct(
        private readonly iterable $resolvers,
        private readonly MessageBusInterface $commandBus,
        private readonly LoggerInterface $logger,
        private readonly int $bulkThreshold,
        private readonly bool $enabled,
    ) {
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        if (!$this->enabled) {
            return;
        }

        $manager = $eventArgs->getObjectManager();
        $unitOfWork = $manager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $this->collect($manager, $entity);
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            // break the recalculation feedback loop: the updater's own flush only touches these
            // two product fields, and that update must not schedule yet another recalculation
            if ($entity instanceof ProductCompletenessAwareInterface) {
                $changedFields = array_keys($unitOfWork->getEntityChangeSet($entity));
                if ([] === array_diff($changedFields, ['completenessRatio', 'completenessRubricVersion'])) {
                    continue;
                }
            }

            $this->collect($manager, $entity);
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            $this->collect($manager, $entity);
        }

        /** @var iterable<\Doctrine\ORM\PersistentCollection<array-key, object>> $collections */
        $collections = [...$unitOfWork->getScheduledCollectionUpdates(), ...$unitOfWork->getScheduledCollectionDeletions()];
        foreach ($collections as $collection) {
            // our own completeness rows are written through a product's collection - skip them
            if (is_a($collection->getTypeClass()->getName(), ProductCompletenessInterface::class, true)) {
                continue;
            }

            $owner = $collection->getOwner();
            if (null !== $owner) {
                $this->collect($manager, $owner);
            }
        }
    }

    public function postFlush(PostFlushEventArgs $eventArgs): void
    {
        try {
            if ([] === $this->buffer) {
                return;
            }

            $productIds = [];
            foreach ($this->buffer as $product) {
                $id = $product->getId();
                if (is_int($id)) {
                    $productIds[$id] = true;
                }
            }

            if (count($productIds) > $this->bulkThreshold) {
                $this->logger->warning(sprintf(
                    'A single flush affected %d products which exceeds the configured bulk threshold (%d). A single catalog-wide recalculation is dispatched instead of per-product recalculations. If this happens during routine operation, consider raising the setono_sylius_completeness.bulk_threshold configuration value',
                    count($productIds),
                    $this->bulkThreshold,
                ));

                $this->dispatch(new RecalculateAllProductsCompleteness());

                return;
            }

            foreach (array_keys($productIds) as $productId) {
                $this->dispatch(new RecalculateProductCompleteness($productId));
            }
        } finally {
            $this->buffer = [];
        }
    }

    private function collect(EntityManagerInterface $manager, object $entity): void
    {
        // entities may be Doctrine proxies, so resolve the real class through the metadata
        /** @var class-string $class */
        $class = $manager->getClassMetadata($entity::class)->getName();

        $resolver = $this->resolveResolver($class);
        if (null === $resolver) {
            return;
        }

        foreach ($resolver->getProducts($entity) as $product) {
            $this->buffer[spl_object_id($product)] = $product;
        }
    }

    /**
     * @param class-string $class
     */
    private function resolveResolver(string $class): ?AffectedProductsResolverInterface
    {
        if (null === $this->resolverMap) {
            $this->resolverMap = [];
        }

        if (array_key_exists($class, $this->resolverMap)) {
            return $this->resolverMap[$class];
        }

        $match = null;
        foreach ($this->resolvers as $resolver) {
            foreach ($resolver->getSupportedClasses() as $supportedClass) {
                if (is_a($class, $supportedClass, true)) {
                    // keep iterating: the LAST registered resolver wins, mirroring checker semantics
                    $match = $resolver;
                }
            }
        }

        return $this->resolverMap[$class] = $match;
    }

    private function dispatch(object $message): void
    {
        $this->commandBus->dispatch(new Envelope($message, [new DispatchAfterCurrentBusStamp()]));
    }
}

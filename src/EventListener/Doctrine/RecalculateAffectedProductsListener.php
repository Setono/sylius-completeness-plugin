<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\EventListener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Psr\Clock\ClockInterface;
use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\AffectedProductsProviderInterface;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessAwareInterface;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessInterface;
use Sylius\Component\Core\Model\ProductInterface;

/**
 * The single capture point of the recalculation pipeline: on every flush it finds the products
 * whose completeness is affected by the inserts, updates, deletes AND scheduled collection changes,
 * and marks them dirty (completenessDirtyAt) so the background drain recalculates them.
 *
 * Marking is done inside onFlush via computeChangeSet/recomputeSingleEntityChangeSet, so the flag is
 * written as part of the same flush - no second EntityManager::flush() and, importantly, nothing in
 * postFlush (Doctrine forbids flushing there). The set of watched classes is derived from the
 * registered AffectedProductsResolverInterface services, so there is no config list to keep in sync
 */
final class RecalculateAffectedProductsListener
{
    /**
     * A product update touching only these fields comes from a recalculation, not a content change,
     * so it must not re-dirty the product (that would be an endless loop)
     */
    private const COMPLETENESS_FIELDS = ['completenessRatio', 'completenessRubricVersion', 'completenessDirtyAt'];

    public function __construct(
        private readonly AffectedProductsProviderInterface $affectedProductsProvider,
        private readonly ClockInterface $clock,
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

        /** @var array<int, ProductInterface> $affected deduplicated by object id */
        $affected = [];

        $collect = function (object $entity) use (&$affected): void {
            foreach ($this->affectedProductsProvider->getProducts($entity) as $product) {
                $affected[spl_object_id($product)] = $product;
            }
        };

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $collect($entity);
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof ProductCompletenessAwareInterface) {
                $changedFields = array_keys($unitOfWork->getEntityChangeSet($entity));
                if ([] === array_diff($changedFields, self::COMPLETENESS_FIELDS)) {
                    continue;
                }
            }

            $collect($entity);
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            $collect($entity);
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
                $collect($owner);
            }
        }

        if ([] === $affected) {
            return;
        }

        $now = $this->clock->now();
        foreach ($affected as $product) {
            if (!$product instanceof ProductCompletenessAwareInterface) {
                continue;
            }

            // a brand-new product is never scored yet, so its null rubric version already makes it a
            // drain candidate - no need to flag it (and computeChangeSet on a queued insert is unsafe)
            if ($unitOfWork->isScheduledForInsert($product)) {
                continue;
            }

            // flag it as part of THIS flush by recomputing the (managed) product's changeset, so no
            // second EntityManager::flush() is needed
            $product->setCompletenessDirtyAt($now);
            $unitOfWork->recomputeSingleEntityChangeSet($manager->getClassMetadata($product::class), $product);
        }
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\EventListener;

use Setono\SyliusCompletenessPlugin\Doctrine\Resolver\AffectedProductsProviderInterface;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessAwareInterface;
use Setono\SyliusCompletenessPlugin\Updater\ProductCompletenessUpdaterInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;

/**
 * The immediate lane: when a product (or one of its variants) is created/updated through the admin
 * resource controller, recalculate the affected product(s) synchronously so the fresh score is
 * visible right away - rather than waiting for the background drain.
 *
 * Bound to the product and product_variant post_create/post_update events. The Doctrine listener has
 * already marked the product dirty during the same request's flush, so it is cleared here before the
 * recalculation persists. Any change that does NOT go through these controllers (API, imports,
 * programmatic writes) still gets picked up by the Doctrine dirty-marker + the drain
 */
final class ProductResourceListener
{
    public function __construct(
        private readonly AffectedProductsProviderInterface $affectedProductsProvider,
        private readonly ProductCompletenessUpdaterInterface $updater,
    ) {
    }

    public function recalculate(ResourceControllerEvent $event): void
    {
        $subject = $event->getSubject();
        if (!is_object($subject)) {
            return;
        }

        foreach ($this->affectedProductsProvider->getProducts($subject) as $product) {
            if ($product instanceof ProductCompletenessAwareInterface) {
                // we are about to recalculate it, so it is no longer waiting for the drain
                $product->setCompletenessDirtyAt(null);
            }

            $this->updater->update($product);
        }
    }
}

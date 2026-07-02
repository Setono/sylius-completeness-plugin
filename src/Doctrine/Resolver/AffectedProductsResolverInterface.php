<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Doctrine\Resolver;

use Sylius\Component\Core\Model\ProductInterface;

/**
 * Answers one question for the onFlush listener: given this changed entity, which product(s)
 * does it affect? Register your own resolver (tag setono_sylius_completeness.affected_products_resolver
 * or just implement this interface with autoconfiguration enabled) to have changes to your own
 * entities trigger recalculation - no core change needed
 */
interface AffectedProductsResolverInterface
{
    /**
     * The entity classes (or interfaces - subclasses match too) this resolver supports
     *
     * @return list<class-string>
     */
    public function getSupportedClasses(): array;

    /**
     * @return iterable<ProductInterface>
     */
    public function getProducts(object $entity): iterable;
}

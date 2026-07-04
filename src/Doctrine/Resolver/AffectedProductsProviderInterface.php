<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Doctrine\Resolver;

use Sylius\Component\Core\Model\ProductInterface;

/**
 * Resolves a changed entity to the product(s) whose completeness it affects, by delegating to the
 * registered AffectedProductsResolverInterface services. Shared by the Doctrine dirty-marking
 * listener and the resource-controller listener so both agree on "what does this change affect"
 */
interface AffectedProductsProviderInterface
{
    /**
     * @return iterable<ProductInterface>
     */
    public function getProducts(object $entity): iterable;
}

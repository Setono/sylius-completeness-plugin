<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Model;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ProductInterface;

/**
 * Applied to the Sylius product resource, so it extends ProductInterface: any completeness aware
 * product is a product, which lets us typehint this single interface wherever we need both
 */
interface ProductCompletenessAwareInterface extends ProductInterface
{
    /**
     * Returns the global completeness ratio (0-100) or null if the product has not been
     * calculated yet or every context turned out to be N/A or excluded
     */
    public function getCompletenessRatio(): ?int;

    public function setCompletenessRatio(?int $completenessRatio): void;

    /**
     * Returns the rubric version this product was last scored against
     */
    public function getCompletenessRubricVersion(): ?int;

    public function setCompletenessRubricVersion(?int $completenessRubricVersion): void;

    /**
     * When set, this product's completeness is out of date (something that affects it changed) and
     * it is queued for the next background recalculation run. Cleared once it has been recalculated
     */
    public function getCompletenessDirtyAt(): ?\DateTimeImmutable;

    public function setCompletenessDirtyAt(?\DateTimeImmutable $completenessDirtyAt): void;

    /**
     * @return Collection<array-key, ProductCompletenessInterface>
     */
    public function getCompletenesses(): Collection;

    public function addCompleteness(ProductCompletenessInterface $completeness): void;

    public function removeCompleteness(ProductCompletenessInterface $completeness): void;

    public function hasCompleteness(ProductCompletenessInterface $completeness): bool;
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Model;

use Doctrine\Common\Collections\Collection;

interface ProductCompletenessAwareInterface
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
     * @return Collection<array-key, ProductCompletenessInterface>
     */
    public function getCompletenesses(): Collection;

    public function addCompleteness(ProductCompletenessInterface $completeness): void;

    public function removeCompleteness(ProductCompletenessInterface $completeness): void;

    public function hasCompleteness(ProductCompletenessInterface $completeness): bool;
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @phpstan-require-implements ProductCompletenessAwareInterface
 * @phpstan-require-implements \Sylius\Component\Core\Model\ProductInterface
 */
trait ProductCompletenessAwareTrait
{
    protected ?int $completenessRatio = null;

    protected ?int $completenessRubricVersion = null;

    /** @var Collection<array-key, ProductCompletenessInterface>|null */
    protected ?Collection $completenesses = null;

    public function getCompletenessRatio(): ?int
    {
        return $this->completenessRatio;
    }

    public function setCompletenessRatio(?int $completenessRatio): void
    {
        $this->completenessRatio = $completenessRatio;
    }

    public function getCompletenessRubricVersion(): ?int
    {
        return $this->completenessRubricVersion;
    }

    public function setCompletenessRubricVersion(?int $completenessRubricVersion): void
    {
        $this->completenessRubricVersion = $completenessRubricVersion;
    }

    /**
     * @return Collection<array-key, ProductCompletenessInterface>
     */
    public function getCompletenesses(): Collection
    {
        return $this->completenesses ??= new ArrayCollection();
    }

    public function addCompleteness(ProductCompletenessInterface $completeness): void
    {
        if (!$this->hasCompleteness($completeness)) {
            $this->getCompletenesses()->add($completeness);
            $completeness->setProduct($this);
        }
    }

    public function removeCompleteness(ProductCompletenessInterface $completeness): void
    {
        if ($this->getCompletenesses()->removeElement($completeness)) {
            $completeness->setProduct(null);
        }
    }

    public function hasCompleteness(ProductCompletenessInterface $completeness): bool
    {
        return $this->getCompletenesses()->contains($completeness);
    }
}

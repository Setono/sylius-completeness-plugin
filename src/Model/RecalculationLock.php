<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Model;

/**
 * A one-row entity backing a leased advisory lock, so at most one background recalculation drain
 * runs at a time. `lockedUntil` is a lease that expires, so a crashed run releases automatically.
 *
 * This entity only exists so that schema tooling creates the table. It is read and written through
 * \Setono\SyliusCompletenessPlugin\Recalculation\RecalculationLockManagerInterface.
 */
class RecalculationLock
{
    public function __construct(
        protected int $id = 1,
        protected ?\DateTimeImmutable $lockedUntil = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLockedUntil(): ?\DateTimeImmutable
    {
        return $this->lockedUntil;
    }

    public function setLockedUntil(?\DateTimeImmutable $lockedUntil): void
    {
        $this->lockedUntil = $lockedUntil;
    }
}

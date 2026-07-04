<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Recalculation;

/**
 * A single leased advisory lock guarding the background recalculation drain, so two runs never
 * overlap. The lease expires after the given TTL, so a crashed run releases the lock automatically.
 */
interface RecalculationLockManagerInterface
{
    /**
     * Tries to acquire the lock for the given number of seconds. Returns false when another run holds
     * a lease that has not expired yet
     */
    public function acquire(int $ttl): bool;

    /**
     * Extends the current lease (call periodically during a long run)
     */
    public function refresh(int $ttl): void;

    public function release(): void;
}

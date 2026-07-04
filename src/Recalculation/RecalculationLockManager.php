<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Recalculation;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Setono\SyliusCompletenessPlugin\Model\RecalculationLock;

/**
 * A leased advisory lock implemented as a single-row table + atomic SQL, so it works on any DBAL
 * platform without requiring the host to configure symfony/lock.
 */
final class RecalculationLockManager implements RecalculationLockManagerInterface
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly ClockInterface $clock,
    ) {
    }

    public function acquire(int $ttl): bool
    {
        $connection = $this->getConnection();
        $table = $this->getTableName();
        $now = $this->clock->now();
        $until = $now->add(new \DateInterval(sprintf('PT%dS', max(1, $ttl))));

        // take the lease only if it is currently free or expired
        $affected = $connection->executeStatement(
            sprintf('UPDATE %s SET locked_until = :until WHERE id = 1 AND (locked_until IS NULL OR locked_until < :now)', $table),
            ['until' => $until, 'now' => $now],
            ['until' => Types::DATETIME_IMMUTABLE, 'now' => Types::DATETIME_IMMUTABLE],
        );
        if ($affected > 0) {
            return true;
        }

        // the row may simply not exist yet - creating it acquires the lease
        try {
            $connection->executeStatement(
                sprintf('INSERT INTO %s (id, locked_until) VALUES (1, :until)', $table),
                ['until' => $until],
                ['until' => Types::DATETIME_IMMUTABLE],
            );

            return true;
        } catch (UniqueConstraintViolationException) {
            // the row exists and is held by an unexpired lease
            return false;
        }
    }

    public function refresh(int $ttl): void
    {
        $until = $this->clock->now()->add(new \DateInterval(sprintf('PT%dS', max(1, $ttl))));

        $this->getConnection()->executeStatement(
            sprintf('UPDATE %s SET locked_until = :until WHERE id = 1', $this->getTableName()),
            ['until' => $until],
            ['until' => Types::DATETIME_IMMUTABLE],
        );
    }

    public function release(): void
    {
        $this->getConnection()->executeStatement(
            sprintf('UPDATE %s SET locked_until = NULL WHERE id = 1', $this->getTableName()),
        );
    }

    private function getConnection(): Connection
    {
        return $this->getManager()->getConnection();
    }

    private function getTableName(): string
    {
        return $this->getManager()->getClassMetadata(RecalculationLock::class)->getTableName();
    }

    private function getManager(): EntityManagerInterface
    {
        $manager = $this->managerRegistry->getManagerForClass(RecalculationLock::class);
        if (!$manager instanceof EntityManagerInterface) {
            throw new \RuntimeException(sprintf('No entity manager found for class %s', RecalculationLock::class));
        }

        return $manager;
    }
}

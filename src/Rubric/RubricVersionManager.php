<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Rubric;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Setono\SyliusCompletenessPlugin\Model\RubricVersion;

final class RubricVersionManager implements RubricVersionManagerInterface
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }

    public function getCurrentVersion(): int
    {
        $version = $this->getConnection()->fetchOne(sprintf('SELECT version FROM %s WHERE id = 1', $this->getTableName()));

        return is_numeric($version) ? (int) $version : 0;
    }

    public function bump(): int
    {
        $connection = $this->getConnection();
        $tableName = $this->getTableName();

        $affectedRows = $connection->executeStatement(sprintf('UPDATE %s SET version = version + 1 WHERE id = 1', $tableName));
        if ($affectedRows > 0) {
            return $this->getCurrentVersion();
        }

        try {
            $connection->executeStatement(sprintf('INSERT INTO %s (id, version) VALUES (1, 1)', $tableName));

            return 1;
        } catch (UniqueConstraintViolationException) {
            // another process inserted the row between our UPDATE and INSERT
            $connection->executeStatement(sprintf('UPDATE %s SET version = version + 1 WHERE id = 1', $tableName));

            return $this->getCurrentVersion();
        }
    }

    private function getConnection(): Connection
    {
        return $this->getManager()->getConnection();
    }

    private function getTableName(): string
    {
        return $this->getManager()->getClassMetadata(RubricVersion::class)->getTableName();
    }

    private function getManager(): EntityManagerInterface
    {
        $manager = $this->managerRegistry->getManagerForClass(RubricVersion::class);
        if (!$manager instanceof EntityManagerInterface) {
            throw new \RuntimeException(sprintf('No entity manager found for class %s', RubricVersion::class));
        }

        return $manager;
    }
}

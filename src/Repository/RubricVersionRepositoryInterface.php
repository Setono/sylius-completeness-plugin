<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Repository;

use Setono\SyliusCompletenessPlugin\Model\RubricVersionInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<RubricVersionInterface>
 */
interface RubricVersionRepositoryInterface extends RepositoryInterface
{
    /**
     * The current rubric version, or 0 when no version row exists yet
     */
    public function findCurrentVersion(): int;

    /**
     * Atomically increments the rubric version at the database level (so concurrent bumps cannot
     * lose an update) and returns the number of affected rows: 0 when the version row does not exist yet
     */
    public function incrementVersion(): int;

    /**
     * Seeds the single version row at version 1. Throws a
     * \Doctrine\DBAL\Exception\UniqueConstraintViolationException when the row already exists
     */
    public function createInitialVersion(): void;
}

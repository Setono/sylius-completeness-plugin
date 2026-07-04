<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Rubric;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Setono\SyliusCompletenessPlugin\Repository\RubricVersionRepositoryInterface;

final class RubricVersionManager implements RubricVersionManagerInterface
{
    public function __construct(private readonly RubricVersionRepositoryInterface $repository)
    {
    }

    public function getCurrentVersion(): int
    {
        return $this->repository->findCurrentVersion();
    }

    public function bump(): int
    {
        if ($this->repository->incrementVersion() > 0) {
            return $this->repository->findCurrentVersion();
        }

        try {
            $this->repository->createInitialVersion();

            return 1;
        } catch (UniqueConstraintViolationException) {
            // another process inserted the row between our increment and insert
            $this->repository->incrementVersion();

            return $this->repository->findCurrentVersion();
        }
    }
}

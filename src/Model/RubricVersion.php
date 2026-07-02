<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Model;

/**
 * A one-row entity holding the monotonically increasing version of the scoring rubric.
 * The version is bumped whenever a completeness rule or context setting changes and is
 * stamped on products at calculation time so stale scores can be flagged in the admin.
 *
 * This entity only exists so that schema tooling creates the table. It is read and
 * written through \Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManagerInterface.
 */
class RubricVersion
{
    public function __construct(protected int $id = 1, protected int $version = 0)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Rubric;

interface RubricVersionManagerInterface
{
    /**
     * Returns the current version of the scoring rubric. Returns 0 if no rubric change has ever been recorded
     */
    public function getCurrentVersion(): int;

    /**
     * Increments the rubric version and returns the new version
     */
    public function bump(): int;
}

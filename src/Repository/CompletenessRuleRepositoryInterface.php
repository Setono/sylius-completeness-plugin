<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Repository;

use Setono\SyliusCompletenessPlugin\Model\CompletenessRuleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<CompletenessRuleInterface>
 */
interface CompletenessRuleRepositoryInterface extends RepositoryInterface
{
    /**
     * Returns the enabled rules ordered by position
     *
     * @return list<CompletenessRuleInterface>
     */
    public function findEnabled(): array;
}

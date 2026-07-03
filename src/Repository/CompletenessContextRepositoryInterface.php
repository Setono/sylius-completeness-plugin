<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Repository;

use Setono\SyliusCompletenessPlugin\Model\CompletenessContextInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<CompletenessContextInterface>
 */
interface CompletenessContextRepositoryInterface extends RepositoryInterface
{
    public function findOneByContext(string $channelCode, string $localeCode): ?CompletenessContextInterface;
}

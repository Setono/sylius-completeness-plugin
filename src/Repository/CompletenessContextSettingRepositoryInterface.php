<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Repository;

use Setono\SyliusCompletenessPlugin\Model\CompletenessContextSettingInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<CompletenessContextSettingInterface>
 */
interface CompletenessContextSettingRepositoryInterface extends RepositoryInterface
{
    public function findOneByContext(string $channelCode, string $localeCode): ?CompletenessContextSettingInterface;
}

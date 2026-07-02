<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Repository;

use Setono\SyliusCompletenessPlugin\Model\CompletenessContextSettingInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class CompletenessContextSettingRepository extends EntityRepository implements CompletenessContextSettingRepositoryInterface
{
    public function findOneByContext(string $channelCode, string $localeCode): ?CompletenessContextSettingInterface
    {
        /** @var CompletenessContextSettingInterface|null $contextSetting */
        $contextSetting = $this->findOneBy([
            'channelCode' => $channelCode,
            'localeCode' => $localeCode,
        ]);

        return $contextSetting;
    }
}

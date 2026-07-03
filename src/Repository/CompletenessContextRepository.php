<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Repository;

use Setono\SyliusCompletenessPlugin\Model\CompletenessContextInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class CompletenessContextRepository extends EntityRepository implements CompletenessContextRepositoryInterface
{
    public function findOneByContext(string $channelCode, string $localeCode): ?CompletenessContextInterface
    {
        /** @var CompletenessContextInterface|null $context */
        $context = $this->findOneBy([
            'channelCode' => $channelCode,
            'localeCode' => $localeCode,
        ]);

        return $context;
    }
}

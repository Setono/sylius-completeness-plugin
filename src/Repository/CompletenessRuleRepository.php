<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Repository;

use Setono\SyliusCompletenessPlugin\Model\CompletenessRuleInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class CompletenessRuleRepository extends EntityRepository implements CompletenessRuleRepositoryInterface
{
    public function findEnabled(): array
    {
        /** @var list<CompletenessRuleInterface> $rules */
        $rules = $this->createQueryBuilder('o')
            ->andWhere('o.enabled = true')
            ->orderBy('o.position', 'ASC')
            ->addOrderBy('o.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $rules;
    }
}

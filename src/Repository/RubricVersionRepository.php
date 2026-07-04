<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Repository;

use Doctrine\ORM\AbstractQuery;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class RubricVersionRepository extends EntityRepository implements RubricVersionRepositoryInterface
{
    public function findCurrentVersion(): int
    {
        // a scalar query so the identity map cannot hand back a stale entity after a bulk increment
        $version = $this->createQueryBuilder('o')
            ->select('o.version')
            ->andWhere('o.id = 1')
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);

        return is_numeric($version) ? (int) $version : 0;
    }

    public function incrementVersion(): int
    {
        $affectedRows = $this->getEntityManager()
            ->createQuery(sprintf('UPDATE %s o SET o.version = o.version + 1 WHERE o.id = 1', $this->getClassName()))
            ->execute();

        return is_int($affectedRows) ? $affectedRows : 0;
    }

    public function createInitialVersion(): void
    {
        // a plain DBAL insert instead of persist()/flush(): the version is bumped from a postFlush
        // listener, so an ORM flush here would re-enter that listener and bump twice
        $metadata = $this->getEntityManager()->getClassMetadata($this->getClassName());

        $this->getEntityManager()->getConnection()->insert($metadata->getTableName(), [
            $metadata->getColumnName('id') => 1,
            $metadata->getColumnName('version') => 1,
        ]);
    }
}

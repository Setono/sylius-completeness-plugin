<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Command;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessAwareInterface;
use Setono\SyliusCompletenessPlugin\Provider\ProductIdsProviderInterface;
use Setono\SyliusCompletenessPlugin\Provider\ProductProviderInterface;
use Setono\SyliusCompletenessPlugin\Recalculation\RecalculationLockManagerInterface;
use Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManagerInterface;
use Setono\SyliusCompletenessPlugin\Updater\ProductCompletenessUpdaterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The background drain, intended to run on a cron (e.g. every few minutes). It recalculates the
 * products that were marked dirty or are stale against the current rubric, in id-keyset chunks,
 * under a leased lock so two runs never overlap.
 */
final class ProcessCommand extends Command
{
    protected static $defaultName = 'setono:completeness:process';

    private const CHUNK_SIZE = 100;

    /**
     * @param class-string $productClass
     */
    public function __construct(
        private readonly RecalculationLockManagerInterface $lockManager,
        private readonly ProductIdsProviderInterface $productIdsProvider,
        private readonly ProductProviderInterface $productProvider,
        private readonly ProductCompletenessUpdaterInterface $updater,
        private readonly RubricVersionManagerInterface $rubricVersionManager,
        private readonly ManagerRegistry $managerRegistry,
        private readonly string $productClass,
        private readonly int $lockTtl,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Recalculate products marked dirty or stale against the current rubric (run this on a cron)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->lockManager->acquire($this->lockTtl)) {
            $io->note('Another completeness recalculation run is already in progress.');

            return Command::SUCCESS;
        }

        $processed = 0;

        try {
            $manager = $this->getManager();
            $currentVersion = $this->rubricVersionManager->getCurrentVersion();

            foreach ($this->productIdsProvider->getRecalculationCandidateChunks(self::CHUNK_SIZE, $currentVersion) as $ids) {
                foreach ($this->productProvider->findByIds($ids) as $product) {
                    if (!$product instanceof ProductCompletenessAwareInterface) {
                        continue;
                    }

                    $dirtyAt = $product->getCompletenessDirtyAt();
                    $id = $product->getId();

                    $this->updater->update($product, bulk: true);

                    if (null !== $dirtyAt && is_int($id)) {
                        $this->clearDirty($id, $dirtyAt);
                    }

                    ++$processed;
                }

                $manager->clear();
                $this->lockManager->refresh($this->lockTtl);
            }
        } finally {
            $this->lockManager->release();
        }

        $io->success(sprintf('Recalculated %d product(s).', $processed));

        return Command::SUCCESS;
    }

    /**
     * Clears the dirty flag only if it has not changed since we picked the product up - so an edit
     * that landed while we were recalculating stays dirty and is retried on the next run
     */
    private function clearDirty(int $id, \DateTimeImmutable $dirtyAt): void
    {
        $manager = $this->getManager();
        $metadata = $manager->getClassMetadata($this->productClass);
        $table = $metadata->getTableName();
        $column = $metadata->getColumnName('completenessDirtyAt');
        $idColumn = $metadata->getSingleIdentifierColumnName();

        $manager->getConnection()->executeStatement(
            sprintf('UPDATE %s SET %s = NULL WHERE %s = :id AND %s = :dirtyAt', $table, $column, $idColumn, $column),
            ['id' => $id, 'dirtyAt' => $dirtyAt],
            ['dirtyAt' => Types::DATETIME_IMMUTABLE],
        );
    }

    private function getManager(): EntityManagerInterface
    {
        $manager = $this->managerRegistry->getManagerForClass($this->productClass);
        if (!$manager instanceof EntityManagerInterface) {
            throw new \RuntimeException(sprintf('No entity manager found for class %s', $this->productClass));
        }

        return $manager;
    }
}

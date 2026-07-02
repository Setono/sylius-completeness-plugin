<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Command;

use Doctrine\Persistence\ManagerRegistry;
use Setono\SyliusCompletenessPlugin\Provider\ProductIdsProviderInterface;
use Setono\SyliusCompletenessPlugin\Provider\ProductProviderInterface;
use Setono\SyliusCompletenessPlugin\Updater\ProductCompletenessUpdaterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'setono:completeness:recalculate',
    description: 'Recalculate the completeness of products (synchronously)',
)]
final class RecalculateCommand extends Command
{
    private const CHUNK_SIZE = 100;

    /**
     * @param class-string $productClass
     */
    public function __construct(
        private readonly ProductIdsProviderInterface $productIdsProvider,
        private readonly ProductProviderInterface $productProvider,
        private readonly ProductCompletenessUpdaterInterface $updater,
        private readonly ManagerRegistry $managerRegistry,
        private readonly string $productClass,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('product', 'p', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Recalculate only the product(s) with the given code(s)')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Recalculate the whole catalog')
            ->setHelp('Run a periodic <info>--all</info> recalculation (e.g. nightly via cron) as a safety net for missed changes.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var list<string> $codes */
        $codes = $input->getOption('product');
        $all = (bool) $input->getOption('all');

        if ($all === ([] !== $codes)) {
            $io->error('Provide either --all or at least one --product=CODE (but not both)');

            return Command::INVALID;
        }

        $progress = $io->createProgressBar();
        $progress->start();

        $processed = 0;
        foreach ($this->productIdsProvider->getChunks(self::CHUNK_SIZE, $all ? null : $codes) as $ids) {
            foreach ($this->productProvider->findByIds($ids) as $product) {
                $this->updater->update($product, bulk: $all);
                $progress->advance();
                ++$processed;
            }

            $this->managerRegistry->getManagerForClass($this->productClass)?->clear();
        }

        $progress->finish();
        $io->newLine(2);
        $io->success(sprintf('Recalculated the completeness of %d product(s)', $processed));

        return Command::SUCCESS;
    }
}

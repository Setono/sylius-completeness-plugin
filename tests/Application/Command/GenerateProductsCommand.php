<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Application\Command;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Bulk-generates lightweight but varied products so the completeness plugin can be exercised at scale.
 *
 * The products deliberately differ in which rules they satisfy (description length, images, taxon,
 * meta description, ...) so the score distribution and recalculation cost are realistic. No image
 * files are created - the image checkers only count ProductImage rows.
 */
final class GenerateProductsCommand extends Command
{
    protected static $defaultName = 'app:completeness:generate-products';

    private const BATCH_SIZE = 200;

    /**
     * @param ProductFactoryInterface<ProductInterface> $productFactory
     * @param FactoryInterface<ChannelPricingInterface> $channelPricingFactory
     * @param FactoryInterface<ProductImageInterface> $productImageFactory
     * @param RepositoryInterface<ChannelInterface> $channelRepository
     * @param RepositoryInterface<TaxonInterface> $taxonRepository
     */
    public function __construct(
        private readonly ProductFactoryInterface $productFactory,
        private readonly FactoryInterface $channelPricingFactory,
        private readonly FactoryInterface $productImageFactory,
        private readonly RepositoryInterface $channelRepository,
        private readonly RepositoryInterface $taxonRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate a large number of varied products for completeness performance testing')
            ->addArgument('count', InputArgument::OPTIONAL, 'How many products to create', '10000')
            ->addArgument('channel', InputArgument::OPTIONAL, 'Channel code to attach the products to', 'FASHION_WEB');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = (int) $input->getArgument('count');
        $channelCode = (string) $input->getArgument('channel');

        $channel = $this->channelRepository->findOneBy(['code' => $channelCode]);
        if (!$channel instanceof ChannelInterface) {
            $io->error(sprintf('Channel "%s" not found.', $channelCode));

            return Command::FAILURE;
        }

        $localeCode = $channel->getDefaultLocale()?->getCode() ?? 'en_US';

        /** @var list<TaxonInterface> $taxons */
        $taxons = array_values(array_filter(
            $this->taxonRepository->findBy([], null, 30),
            static fn ($t): bool => $t instanceof TaxonInterface,
        ));

        $suffix = substr(bin2hex(random_bytes(3)), 0, 6);
        $io->progressStart($count);

        for ($i = 1; $i <= $count; ++$i) {
            $this->createProduct($i, $suffix, $channel, $localeCode, $taxons);

            if (0 === $i % self::BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                // the channel/taxon references are detached by clear(); re-fetch them for the next batch
                $channel = $this->channelRepository->findOneBy(['code' => $channelCode]);
                $taxons = array_values(array_filter(
                    $this->taxonRepository->findBy([], null, 30),
                    static fn ($t): bool => $t instanceof TaxonInterface,
                ));
            }

            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->success(sprintf('Created %d products in channel %s. Run "setono:completeness:recalculate --all" to score them.', $count, $channelCode));

        return Command::SUCCESS;
    }

    /**
     * @param list<TaxonInterface> $taxons
     */
    private function createProduct(int $i, string $suffix, ChannelInterface $channel, string $localeCode, array $taxons): void
    {
        $code = sprintf('perf_%s_%d', $suffix, $i);

        /** @var ProductInterface $product */
        $product = $this->productFactory->createWithVariant();
        $product->setCode($code);
        $product->setEnabled(true);
        $product->addChannel($channel);

        $product->setCurrentLocale($localeCode);
        $product->setFallbackLocale($localeCode);
        $product->setName(sprintf('Performance product %d', $i));
        $product->setSlug($code);

        // vary the enrichment fields so completeness scores spread across the whole 0-100 range
        if (0 !== $i % 10) {
            $wordCount = 0 === $i % 3 ? 120 : 25;
            $product->setDescription(trim(str_repeat('lorem ipsum dolor ', (int) ceil($wordCount / 3))));
        }
        if (0 !== $i % 4) {
            $product->setShortDescription('A short description for product ' . $i);
        }
        if (0 === $i % 2) {
            $product->setMetaDescription('Meta description for product ' . $i);
        }
        if (0 !== $i % 6 && [] !== $taxons) {
            $product->setMainTaxon($taxons[$i % count($taxons)]);
        }

        $imageCount = $i % 5; // 0..4 images
        for ($n = 0; $n < $imageCount; ++$n) {
            /** @var ProductImageInterface $image */
            $image = $this->productImageFactory->createNew();
            $image->setPath(sprintf('perf/%s_%d.jpg', $code, $n));
            $product->addImage($image);
        }

        /** @var ProductVariantInterface $variant */
        $variant = $product->getVariants()->first();
        $variant->setCode($code);
        $variant->setEnabled(true);

        /** @var ChannelPricingInterface $channelPricing */
        $channelPricing = $this->channelPricingFactory->createNew();
        $channelPricing->setChannelCode((string) $channel->getCode());
        $channelPricing->setPrice(1500 + $i);
        $variant->addChannelPricing($channelPricing);

        $this->entityManager->persist($product);
    }
}

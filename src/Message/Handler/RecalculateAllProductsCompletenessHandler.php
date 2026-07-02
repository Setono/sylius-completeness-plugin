<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Message\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\SyliusCompletenessPlugin\Message\Command\RecalculateAllProductsCompleteness;
use Setono\SyliusCompletenessPlugin\Provider\ProductIdsProviderInterface;
use Setono\SyliusCompletenessPlugin\Provider\ProductProviderInterface;
use Setono\SyliusCompletenessPlugin\Updater\ProductCompletenessUpdaterInterface;

final class RecalculateAllProductsCompletenessHandler
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
    }

    public function __invoke(RecalculateAllProductsCompleteness $message): void
    {
        foreach ($this->productIdsProvider->getChunks(self::CHUNK_SIZE) as $ids) {
            foreach ($this->productProvider->findByIds($ids) as $product) {
                $this->updater->update($product, bulk: true);
            }

            // bound memory: detach the processed chunk before loading the next one
            $this->managerRegistry->getManagerForClass($this->productClass)?->clear();
        }
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Message\Handler;

use Setono\SyliusCompletenessPlugin\Message\Command\RecalculateProductCompleteness;
use Setono\SyliusCompletenessPlugin\Provider\ProductProviderInterface;
use Setono\SyliusCompletenessPlugin\Updater\ProductCompletenessUpdaterInterface;

final class RecalculateProductCompletenessHandler
{
    public function __construct(
        private readonly ProductProviderInterface $productProvider,
        private readonly ProductCompletenessUpdaterInterface $updater,
    ) {
    }

    public function __invoke(RecalculateProductCompleteness $message): void
    {
        $product = $this->productProvider->findById($message->productId);

        // the product may have been deleted between dispatch and handling
        if (null === $product) {
            return;
        }

        $this->updater->update($product, $message->bulk);
    }
}

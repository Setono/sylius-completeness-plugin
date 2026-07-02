<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Checker;

use Sylius\Component\Core\Model\ProductInterface;

final class IsEnabledChecker extends BinaryChecker
{
    public static function getType(): string
    {
        return 'is_enabled';
    }

    protected function isSatisfied(ProductInterface $product, CompletenessCheckContext $context, array $configuration): bool
    {
        return $product->isEnabled();
    }
}

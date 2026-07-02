<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Checker;

use Setono\SyliusCompletenessPlugin\Exception\InvalidCheckerConfigurationException;
use Sylius\Component\Core\Model\ProductInterface;

/**
 * A graded checker: 3 images out of a required 5 scores 0.6
 */
final class HasMinimumImagesChecker implements CompletenessCheckerInterface
{
    public static function getType(): string
    {
        return 'has_minimum_images';
    }

    public function score(ProductInterface $product, CompletenessCheckContext $context, array $configuration): float
    {
        $count = $configuration['count'] ?? null;
        if (!is_numeric($count) || (int) $count < 1) {
            throw new InvalidCheckerConfigurationException('The has_minimum_images checker expects a "count" configuration value of 1 or more');
        }

        return min($product->getImages()->count() / (int) $count, 1.0);
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Checker;

use Sylius\Component\Core\Model\ProductInterface;

/**
 * Base class for checkers that are either fully met or not met at all
 */
abstract class BinaryChecker implements CompletenessCheckerInterface
{
    final public function score(ProductInterface $product, CompletenessCheckContext $context, array $configuration): float
    {
        return $this->isSatisfied($product, $context, $configuration) ? 1.0 : 0.0;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    abstract protected function isSatisfied(ProductInterface $product, CompletenessCheckContext $context, array $configuration): bool;
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Preview;

use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Sylius\Component\Core\Model\ProductInterface;

interface ScratchpadEvaluatorInterface
{
    public function evaluate(ProductInterface $product, CompletenessCheckContext $context, string $expression): ScratchpadResult;
}

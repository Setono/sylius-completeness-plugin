<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\ViewModel;

use Sylius\Component\Core\Model\ProductInterface;

interface CompletenessPanelFactoryInterface
{
    public function create(ProductInterface $product): CompletenessPanel;
}

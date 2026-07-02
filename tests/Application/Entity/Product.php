<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Application\Entity;

use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessAwareInterface;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessAwareTrait;
use Sylius\Component\Core\Model\Product as BaseProduct;

class Product extends BaseProduct implements ProductCompletenessAwareInterface
{
    use ProductCompletenessAwareTrait;
}

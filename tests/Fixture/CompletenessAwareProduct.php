<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Fixture;

use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessAwareInterface;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessAwareTrait;
use Sylius\Component\Core\Model\Product as BaseProduct;

/**
 * A completeness aware product for unit tests, with a settable id
 */
class CompletenessAwareProduct extends BaseProduct implements ProductCompletenessAwareInterface
{
    use ProductCompletenessAwareTrait;

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}

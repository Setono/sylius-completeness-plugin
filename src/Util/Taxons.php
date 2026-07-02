<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Util;

use Sylius\Component\Core\Model\ProductInterface;

final class Taxons
{
    private function __construct()
    {
    }

    /**
     * Returns the unique codes of the main taxon and the product taxons
     *
     * @return list<string>
     */
    public static function codes(ProductInterface $product): array
    {
        $codes = [];

        $mainTaxonCode = $product->getMainTaxon()?->getCode();
        if (null !== $mainTaxonCode) {
            $codes[$mainTaxonCode] = true;
        }

        foreach ($product->getTaxons() as $taxon) {
            $code = $taxon->getCode();
            if (null !== $code) {
                $codes[$code] = true;
            }
        }

        return array_keys($codes);
    }
}

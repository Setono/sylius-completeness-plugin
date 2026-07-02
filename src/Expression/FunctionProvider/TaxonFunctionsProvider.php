<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression\FunctionProvider;

use Setono\SyliusCompletenessPlugin\Util\Text;
use Sylius\Component\Core\Model\ProductInterface;

final class TaxonFunctionsProvider extends FunctionProvider
{
    public function getFunctions(): array
    {
        return [
            $this->createFunction(
                'has_main_taxon',
                fn (array $variables, mixed $product): bool => null !== $this->assertProduct($product, 'has_main_taxon')->getMainTaxon(),
            ),
            $this->createFunction(
                'in_taxon',
                function (array $variables, mixed $product, mixed $taxonCode): bool {
                    $product = $this->assertProduct($product, 'in_taxon');

                    return in_array(Text::coerce($taxonCode), self::taxonCodes($product), true);
                },
            ),
            $this->createFunction(
                'taxon_codes',
                fn (array $variables, mixed $product): array => self::taxonCodes($this->assertProduct($product, 'taxon_codes')),
            ),
            $this->createFunction(
                'taxon_count',
                fn (array $variables, mixed $product): int => count(self::taxonCodes($this->assertProduct($product, 'taxon_count'))),
            ),
        ];
    }

    /**
     * Returns the unique codes of the main taxon and the product taxons
     *
     * @return list<string>
     */
    private static function taxonCodes(ProductInterface $product): array
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

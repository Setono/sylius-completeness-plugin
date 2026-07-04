<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression\FunctionProvider;

use Setono\SyliusCompletenessPlugin\Util\Taxons;
use Setono\SyliusCompletenessPlugin\Util\Text;

final class TaxonFunctionsProvider extends FunctionProvider
{
    public function getFunctions(): array
    {
        return [
            $this->createFunction(
                'has_main_taxon',
                'has_main_taxon(product): bool',
                'True when the product has a main taxon.',
                fn (array $variables, mixed $product): bool => null !== $this->assertProduct($product, 'has_main_taxon')->getMainTaxon(),
            ),
            $this->createFunction(
                'in_taxon',
                'in_taxon(product, taxonCode): bool',
                'True when the product is in the given taxon (its main taxon or any of its product taxons).',
                function (array $variables, mixed $product, mixed $taxonCode): bool {
                    $product = $this->assertProduct($product, 'in_taxon');

                    return in_array(Text::coerce($taxonCode), Taxons::codes($product), true);
                },
            ),
            $this->createFunction(
                'taxon_codes',
                'taxon_codes(product): list',
                'The codes of every taxon the product belongs to, as a list.',
                fn (array $variables, mixed $product): array => Taxons::codes($this->assertProduct($product, 'taxon_codes')),
            ),
            $this->createFunction(
                'taxon_count',
                'taxon_count(product): int',
                'The number of taxons the product belongs to.',
                fn (array $variables, mixed $product): int => count(Taxons::codes($this->assertProduct($product, 'taxon_count'))),
            ),
        ];
    }
}

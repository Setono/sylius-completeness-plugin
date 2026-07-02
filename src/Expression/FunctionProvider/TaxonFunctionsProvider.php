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
                fn (array $variables, mixed $product): bool => null !== $this->assertProduct($product, 'has_main_taxon')->getMainTaxon(),
            ),
            $this->createFunction(
                'in_taxon',
                function (array $variables, mixed $product, mixed $taxonCode): bool {
                    $product = $this->assertProduct($product, 'in_taxon');

                    return in_array(Text::coerce($taxonCode), Taxons::codes($product), true);
                },
            ),
            $this->createFunction(
                'taxon_codes',
                fn (array $variables, mixed $product): array => Taxons::codes($this->assertProduct($product, 'taxon_codes')),
            ),
            $this->createFunction(
                'taxon_count',
                fn (array $variables, mixed $product): int => count(Taxons::codes($this->assertProduct($product, 'taxon_count'))),
            ),
        ];
    }
}

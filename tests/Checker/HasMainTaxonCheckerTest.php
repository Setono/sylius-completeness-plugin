<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Checker;

use Setono\SyliusCompletenessPlugin\Checker\HasMainTaxonChecker;
use Sylius\Component\Core\Model\Taxon;

final class HasMainTaxonCheckerTest extends CheckerTestCase
{
    /**
     * @test
     */
    public function it_scores_one_when_a_main_taxon_is_set(): void
    {
        $taxon = new Taxon();
        $taxon->setCode('shirts');

        $product = $this->createProduct();
        $product->setMainTaxon($taxon);

        self::assertSame(1.0, (new HasMainTaxonChecker())->score($product, $this->createContext(), []));
    }

    /**
     * @test
     */
    public function it_scores_zero_when_no_main_taxon_is_set(): void
    {
        self::assertSame(0.0, (new HasMainTaxonChecker())->score($this->createProduct(), $this->createContext(), []));
    }
}

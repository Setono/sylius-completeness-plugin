<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Checker;

use Setono\SyliusCompletenessPlugin\Checker\HasMetaKeywordsChecker;

final class HasMetaKeywordsCheckerTest extends CheckerTestCase
{
    /**
     * @test
     */
    public function it_scores_one_when_the_meta_keywords_are_set(): void
    {
        $product = $this->createProduct();
        $product->setMetaKeywords('shirt, cotton');

        self::assertSame(1.0, (new HasMetaKeywordsChecker())->score($product, $this->createContext(), []));
    }

    /**
     * @test
     */
    public function it_scores_zero_when_the_meta_keywords_are_not_set(): void
    {
        self::assertSame(0.0, (new HasMetaKeywordsChecker())->score($this->createProduct(), $this->createContext(), []));
    }
}

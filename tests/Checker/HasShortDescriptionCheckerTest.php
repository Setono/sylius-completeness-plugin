<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Checker;

use Setono\SyliusCompletenessPlugin\Checker\HasShortDescriptionChecker;

final class HasShortDescriptionCheckerTest extends CheckerTestCase
{
    /**
     * @test
     */
    public function it_scores_one_when_the_short_description_is_set(): void
    {
        $product = $this->createProduct();
        $product->setShortDescription('A nice shirt');

        self::assertSame(1.0, (new HasShortDescriptionChecker())->score($product, $this->createContext(), []));
    }

    /**
     * @test
     */
    public function it_scores_zero_when_the_short_description_is_not_set(): void
    {
        self::assertSame(0.0, (new HasShortDescriptionChecker())->score($this->createProduct(), $this->createContext(), []));
    }
}

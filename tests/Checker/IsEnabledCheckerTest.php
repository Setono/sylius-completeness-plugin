<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Checker;

use Setono\SyliusCompletenessPlugin\Checker\IsEnabledChecker;

final class IsEnabledCheckerTest extends CheckerTestCase
{
    /**
     * @test
     */
    public function it_has_a_type(): void
    {
        self::assertSame('is_enabled', IsEnabledChecker::getType());
    }

    /**
     * @test
     */
    public function it_scores_one_when_the_product_is_enabled(): void
    {
        $product = $this->createProduct();
        $product->setEnabled(true);

        self::assertSame(1.0, (new IsEnabledChecker())->score($product, $this->createContext(), []));
    }

    /**
     * @test
     */
    public function it_scores_zero_when_the_product_is_disabled(): void
    {
        $product = $this->createProduct();
        $product->setEnabled(false);

        self::assertSame(0.0, (new IsEnabledChecker())->score($product, $this->createContext(), []));
    }
}

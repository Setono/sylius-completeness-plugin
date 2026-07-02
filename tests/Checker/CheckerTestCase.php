<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Checker;

use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Locale\Model\Locale;

abstract class CheckerTestCase extends TestCase
{
    /**
     * Creates a product with the current AND fallback locale set to the given locale,
     * mirroring what the calculator does before evaluating a context (spec §4 step 0)
     */
    protected function createProduct(string $localeCode = 'en'): Product
    {
        $product = new Product();
        $product->setCurrentLocale($localeCode);
        $product->setFallbackLocale($localeCode);

        return $product;
    }

    protected function createContext(string $channelCode = 'WEB', string $localeCode = 'en'): CompletenessCheckContext
    {
        $channel = new Channel();
        $channel->setCode($channelCode);

        $locale = new Locale();
        $locale->setCode($localeCode);

        return new CompletenessCheckContext($channel, $locale);
    }
}

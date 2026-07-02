<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Expression;

use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionEvaluator;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionLanguageFactory;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Locale\Model\Locale;

final class ExpressionEvaluatorTest extends TestCase
{
    /**
     * @test
     */
    public function it_exposes_the_standard_variables(): void
    {
        $channel = new Channel();
        $channel->setCode('WEB');

        $locale = new Locale();
        $locale->setCode('da');

        $context = new CompletenessCheckContext($channel, $locale);

        $product = new Product();
        $product->setCurrentLocale('da');
        $product->setFallbackLocale('da');
        $product->setName('Trøje');

        $evaluator = new ExpressionEvaluator(ExpressionLanguageFactory::create([]));

        self::assertSame('WEB', $evaluator->evaluate('channelCode', $product, $context));
        self::assertSame('da', $evaluator->evaluate('localeCode', $product, $context));
        self::assertSame($channel, $evaluator->evaluate('channel', $product, $context));
        self::assertSame($locale, $evaluator->evaluate('locale', $product, $context));
        self::assertSame('Trøje', $evaluator->evaluate('product.getName()', $product, $context));
    }

    /**
     * @test
     */
    public function it_reads_translatable_fields_natively_without_locale_fallback(): void
    {
        $channel = new Channel();
        $channel->setCode('WEB');

        $locale = new Locale();
        $locale->setCode('da');

        $context = new CompletenessCheckContext($channel, $locale);

        $product = new Product();
        $product->setCurrentLocale('en');
        $product->setFallbackLocale('en');
        $product->setName('Shirt');

        // the calculator sets both locales to the context locale before evaluating
        $product->setCurrentLocale('da');
        $product->setFallbackLocale('da');

        $evaluator = new ExpressionEvaluator(ExpressionLanguageFactory::create([]));

        self::assertNull($evaluator->evaluate('product.getName()', $product, $context));
    }
}

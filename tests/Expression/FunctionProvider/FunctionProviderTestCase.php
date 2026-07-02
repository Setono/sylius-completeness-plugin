<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Expression\FunctionProvider;

use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Setono\SyliusCompletenessPlugin\Context\CalculationContext;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionLanguageFactory;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\AttributeFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\ChannelFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\CollectionFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\ImageFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\TaxonFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\TextFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\TranslationFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\VariantFunctionsProvider;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Locale\Model\Locale;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

abstract class FunctionProviderTestCase extends TestCase
{
    protected CalculationContext $calculationContext;

    protected ExpressionLanguage $expressionLanguage;

    protected function setUp(): void
    {
        $this->calculationContext = new CalculationContext();
        $this->expressionLanguage = ExpressionLanguageFactory::create([
            new TextFunctionsProvider(),
            new TranslationFunctionsProvider($this->calculationContext),
            new AttributeFunctionsProvider($this->calculationContext),
            new ImageFunctionsProvider(),
            new TaxonFunctionsProvider(),
            new VariantFunctionsProvider(),
            new ChannelFunctionsProvider($this->calculationContext),
            new CollectionFunctionsProvider(),
        ]);
    }

    /**
     * @param array<string, mixed> $values
     */
    protected function evaluate(string $expression, array $values = []): mixed
    {
        return $this->expressionLanguage->evaluate($expression, $values);
    }

    /**
     * Publishes an active calculation context, as the calculator does before evaluating rules
     */
    protected function publishContext(string $channelCode = 'WEB', string $localeCode = 'en'): void
    {
        $channel = new Channel();
        $channel->setCode($channelCode);

        $locale = new Locale();
        $locale->setCode($localeCode);

        $this->calculationContext->set(new CompletenessCheckContext($channel, $locale));
    }

    protected function createProduct(string $localeCode = 'en'): Product
    {
        $product = new Product();
        $product->setCurrentLocale($localeCode);
        $product->setFallbackLocale($localeCode);

        return $product;
    }
}

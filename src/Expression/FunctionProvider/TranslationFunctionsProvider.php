<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression\FunctionProvider;

use Setono\SyliusCompletenessPlugin\Context\CalculationContextInterface;

final class TranslationFunctionsProvider extends FunctionProvider
{
    public function __construct(private readonly CalculationContextInterface $calculationContext)
    {
    }

    public function getFunctions(): array
    {
        return [
            $this->createFunction(
                'has_translation',
                function (array $variables, mixed $product, mixed $locale = null): bool {
                    $product = $this->assertProduct($product, 'has_translation');
                    $localeCode = $this->toNullableString($locale) ?? $this->calculationContext->get()->getLocaleCode();

                    foreach ($product->getTranslations() as $translation) {
                        if ($translation->getLocale() === $localeCode) {
                            return true;
                        }
                    }

                    return false;
                },
            ),
        ];
    }
}

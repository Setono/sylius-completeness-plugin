<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Expression\FunctionProvider;

use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Sylius\Component\Attribute\Model\AttributeValueInterface;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Product\Model\ProductAttribute;
use Sylius\Component\Product\Model\ProductAttributeValue;

final class AttributeFunctionsProviderTest extends FunctionProviderTestCase
{
    /**
     * @test
     */
    public function it_checks_attribute_presence_with_the_implicit_locale(): void
    {
        $product = $this->createProduct();
        $this->addTextAttribute($product, 'material', 'wool', 'en');

        $this->publishContext('WEB', 'en');
        self::assertTrue($this->evaluate('has_attribute(product, "material")', ['product' => $product]));

        $this->publishContext('WEB', 'da');
        self::assertFalse($this->evaluate('has_attribute(product, "material")', ['product' => $product]));
    }

    /**
     * @test
     */
    public function it_checks_attribute_presence_with_an_explicit_locale(): void
    {
        $product = $this->createProduct();
        $this->addTextAttribute($product, 'material', 'wool', 'en');

        $this->publishContext('WEB', 'da');

        self::assertTrue($this->evaluate('has_attribute(product, "material", "en")', ['product' => $product]));
    }

    /**
     * @test
     */
    public function it_returns_the_scalar_attribute_value(): void
    {
        $product = $this->createProduct();
        $this->addTextAttribute($product, 'material', 'wool', 'en');

        $this->publishContext('WEB', 'en');

        self::assertSame('wool', $this->evaluate('attribute_value(product, "material")', ['product' => $product]));
        self::assertSame('', $this->evaluate('attribute_value(product, "missing")', ['product' => $product]));
    }

    /**
     * @test
     */
    public function it_returns_the_first_option_code_for_select_attributes(): void
    {
        $product = $this->createProduct();
        $this->addSelectAttribute($product, 'type', ['beer', 'lager']);

        $this->publishContext('WEB', 'en');

        self::assertSame('beer', $this->evaluate('attribute_value(product, "type")', ['product' => $product]));
        self::assertSame(['beer', 'lager'], $this->evaluate('attribute_values(product, "type")', ['product' => $product]));
    }

    /**
     * @test
     */
    public function it_wraps_scalar_values_in_a_list(): void
    {
        $product = $this->createProduct();
        $this->addTextAttribute($product, 'material', 'wool', 'en');

        $this->publishContext('WEB', 'en');

        self::assertSame(['wool'], $this->evaluate('attribute_values(product, "material")', ['product' => $product]));
        self::assertSame([], $this->evaluate('attribute_values(product, "missing")', ['product' => $product]));
    }

    /**
     * @test
     */
    public function it_counts_non_empty_attributes_for_the_context_locale(): void
    {
        $product = $this->createProduct();
        $this->addTextAttribute($product, 'material', 'wool', 'en');
        $this->addTextAttribute($product, 'fit', 'slim', 'da');
        $this->addTextAttribute($product, 'ean', '1234', null);
        $this->addTextAttribute($product, 'empty', '', 'en');

        $this->publishContext('WEB', 'en');

        // material (en) + ean (non localizable) — fit is da, empty is blank
        self::assertSame(2, $this->evaluate('attribute_count(product)', ['product' => $product]));
    }

    private function addTextAttribute(Product $product, string $code, string $value, ?string $localeCode): void
    {
        $attribute = new ProductAttribute();
        $attribute->setCode($code);
        $attribute->setType(TextAttributeType::TYPE);
        $attribute->setStorageType(AttributeValueInterface::STORAGE_TEXT);

        $attributeValue = new ProductAttributeValue();
        $attributeValue->setAttribute($attribute);
        $attributeValue->setValue($value);
        $attributeValue->setLocaleCode($localeCode);

        $product->addAttribute($attributeValue);
    }

    /**
     * @param list<string> $optionCodes
     */
    private function addSelectAttribute(Product $product, string $code, array $optionCodes): void
    {
        $attribute = new ProductAttribute();
        $attribute->setCode($code);
        $attribute->setType(SelectAttributeType::TYPE);
        $attribute->setStorageType(AttributeValueInterface::STORAGE_JSON);

        $attributeValue = new ProductAttributeValue();
        $attributeValue->setAttribute($attribute);
        $attributeValue->setValue($optionCodes);
        $attributeValue->setLocaleCode('en');

        $product->addAttribute($attributeValue);
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Checker;

use Setono\SyliusCompletenessPlugin\Checker\HasAttributeChecker;
use Setono\SyliusCompletenessPlugin\Exception\InvalidCheckerConfigurationException;
use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Sylius\Component\Attribute\Model\AttributeValueInterface;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Product\Model\ProductAttribute;
use Sylius\Component\Product\Model\ProductAttributeValue;

final class HasAttributeCheckerTest extends CheckerTestCase
{
    /**
     * @test
     */
    public function it_scores_one_when_the_attribute_has_a_value_in_the_context_locale(): void
    {
        $product = $this->createProductWithAttribute('material', 'wool', 'da');

        self::assertSame(1.0, (new HasAttributeChecker())->score(
            $product,
            $this->createContext('WEB', 'da'),
            ['attributeCode' => 'material'],
        ));
    }

    /**
     * @test
     */
    public function it_scores_zero_when_the_attribute_only_has_a_value_in_another_locale(): void
    {
        $product = $this->createProductWithAttribute('material', 'wool', 'en');

        self::assertSame(0.0, (new HasAttributeChecker())->score(
            $product,
            $this->createContext('WEB', 'da'),
            ['attributeCode' => 'material'],
        ));
    }

    /**
     * @test
     */
    public function it_scores_one_for_a_non_localizable_attribute_regardless_of_locale(): void
    {
        $product = $this->createProductWithAttribute('ean', '1234567890123', null);

        self::assertSame(1.0, (new HasAttributeChecker())->score(
            $product,
            $this->createContext('WEB', 'da'),
            ['attributeCode' => 'ean'],
        ));
    }

    /**
     * @test
     */
    public function it_scores_zero_when_the_attribute_value_is_blank(): void
    {
        $product = $this->createProductWithAttribute('material', '   ', 'en');

        self::assertSame(0.0, (new HasAttributeChecker())->score(
            $product,
            $this->createContext('WEB', 'en'),
            ['attributeCode' => 'material'],
        ));
    }

    /**
     * @test
     */
    public function it_scores_zero_when_the_product_does_not_have_the_attribute(): void
    {
        self::assertSame(0.0, (new HasAttributeChecker())->score(
            $this->createProduct(),
            $this->createContext(),
            ['attributeCode' => 'material'],
        ));
    }

    /**
     * @test
     */
    public function it_throws_when_the_attribute_code_is_missing(): void
    {
        $this->expectException(InvalidCheckerConfigurationException::class);

        (new HasAttributeChecker())->score($this->createProduct(), $this->createContext(), []);
    }

    private function createProductWithAttribute(string $code, string $value, ?string $localeCode): Product
    {
        $attribute = new ProductAttribute();
        $attribute->setCode($code);
        $attribute->setType(TextAttributeType::TYPE);
        $attribute->setStorageType(AttributeValueInterface::STORAGE_TEXT);

        $attributeValue = new ProductAttributeValue();
        $attributeValue->setAttribute($attribute);
        $attributeValue->setValue($value);
        $attributeValue->setLocaleCode($localeCode);

        $product = $this->createProduct();
        $product->addAttribute($attributeValue);

        return $product;
    }
}

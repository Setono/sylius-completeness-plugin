<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression;

final class ExpressionFunctionDocumentationProvider implements ExpressionFunctionDocumentationProviderInterface
{
    public function getDocumentation(): array
    {
        return [
            // text
            'word_count' => [
                'signature' => 'word_count(text)',
                'description' => 'The number of words in the text after HTML is stripped (Unicode-safe; null counts as 0).',
            ],
            'char_count' => [
                'signature' => 'char_count(text)',
                'description' => 'The number of characters in the text after HTML is stripped (Unicode-safe; null counts as 0).',
            ],
            'is_blank' => [
                'signature' => 'is_blank(text)',
                'description' => 'True when the text is null or empty after HTML is stripped and trimmed.',
            ],
            'lower' => [
                'signature' => 'lower(text)',
                'description' => 'The text converted to lower case (Unicode-safe).',
            ],
            'upper' => [
                'signature' => 'upper(text)',
                'description' => 'The text converted to upper case (Unicode-safe).',
            ],
            'trim' => [
                'signature' => 'trim(text)',
                'description' => 'The text with leading and trailing whitespace removed.',
            ],
            'icontains' => [
                'signature' => 'icontains(text, needle)',
                'description' => 'True when the text contains the needle (case-insensitive).',
            ],
            'starts_with' => [
                'signature' => 'starts_with(text, prefix)',
                'description' => 'True when the text starts with the given prefix (case-sensitive).',
            ],
            'ends_with' => [
                'signature' => 'ends_with(text, suffix)',
                'description' => 'True when the text ends with the given suffix (case-sensitive).',
            ],
            // translation
            'has_translation' => [
                'signature' => 'has_translation(product[, locale])',
                'description' => 'True when a real translation row exists for the locale (never falls back to the default locale).',
            ],
            // attributes
            'has_attribute' => [
                'signature' => 'has_attribute(product, code[, locale])',
                'description' => 'True when the product has a non-empty value for the attribute code.',
            ],
            'attribute_value' => [
                'signature' => 'attribute_value(product, code[, locale])',
                'description' => 'The product\'s value for the attribute code; for select attributes this is the stored option code, not the label.',
            ],
            'attribute_values' => [
                'signature' => 'attribute_values(product, code[, locale])',
                'description' => 'The product\'s values for a multi-value attribute code, as a list.',
            ],
            'attribute_count' => [
                'signature' => 'attribute_count(product)',
                'description' => 'The number of attribute values set on the product.',
            ],
            // images
            'image_count' => [
                'signature' => 'image_count(product)',
                'description' => 'The number of images on the product.',
            ],
            'has_image' => [
                'signature' => 'has_image(product)',
                'description' => 'True when the product has at least one image.',
            ],
            'image_count_of_type' => [
                'signature' => 'image_count_of_type(product, type)',
                'description' => 'The number of images of the given type on the product.',
            ],
            'has_image_type' => [
                'signature' => 'has_image_type(product, type)',
                'description' => 'True when the product has at least one image of the given type.',
            ],
            // taxons
            'has_main_taxon' => [
                'signature' => 'has_main_taxon(product)',
                'description' => 'True when the product has a main taxon.',
            ],
            'in_taxon' => [
                'signature' => 'in_taxon(product, taxonCode)',
                'description' => 'True when the product is in the given taxon (its main taxon or any of its product taxons).',
            ],
            'taxon_codes' => [
                'signature' => 'taxon_codes(product)',
                'description' => 'The codes of every taxon the product belongs to, as a list.',
            ],
            'taxon_count' => [
                'signature' => 'taxon_count(product)',
                'description' => 'The number of taxons the product belongs to.',
            ],
            // variants and options
            'variant_count' => [
                'signature' => 'variant_count(product)',
                'description' => 'The number of variants of the product.',
            ],
            'enabled_variant_count' => [
                'signature' => 'enabled_variant_count(product)',
                'description' => 'The number of enabled variants of the product.',
            ],
            'has_option' => [
                'signature' => 'has_option(product, optionCode)',
                'description' => 'True when the product has the given product option.',
            ],
            'option_count' => [
                'signature' => 'option_count(product)',
                'description' => 'The number of product options on the product.',
            ],
            'association_count' => [
                'signature' => 'association_count(product, typeCode)',
                'description' => 'The number of associated products for the given association type code.',
            ],
            // channel and pricing
            'is_enabled' => [
                'signature' => 'is_enabled(product)',
                'description' => 'True when the product is enabled.',
            ],
            'is_in_channel' => [
                'signature' => 'is_in_channel(product[, channelCode])',
                'description' => 'True when the product is assigned to the channel.',
            ],
            'channel_count' => [
                'signature' => 'channel_count(product)',
                'description' => 'The number of channels the product is assigned to.',
            ],
            'has_price' => [
                'signature' => 'has_price(product[, channelCode])',
                'description' => 'True when at least one enabled variant is priced in the channel.',
            ],
            'price' => [
                'signature' => 'price(product[, channelCode])',
                'description' => 'The lowest enabled-variant price in the channel, in minor units (0 when the product is not priced).',
            ],
            // collections and math
            'count' => [
                'signature' => 'count(list)',
                'description' => 'The number of items in a list.',
            ],
            'is_empty' => [
                'signature' => 'is_empty(list)',
                'description' => 'True when the list has no items.',
            ],
            'min' => [
                'signature' => 'min(a, b)',
                'description' => 'The smaller of the two numbers.',
            ],
            'max' => [
                'signature' => 'max(a, b)',
                'description' => 'The larger of the two numbers.',
            ],
            'between' => [
                'signature' => 'between(value, low, high)',
                'description' => 'True when the value is between low and high (inclusive).',
            ],
        ];
    }
}

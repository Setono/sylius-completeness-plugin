<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Expression\FunctionProvider;

final class TextFunctionsProviderTest extends FunctionProviderTestCase
{
    /**
     * @test
     */
    public function it_counts_words_in_html(): void
    {
        self::assertSame(2, $this->evaluate('word_count(text)', ['text' => '<p>foo bar</p>']));
    }

    /**
     * @test
     */
    public function it_does_not_merge_words_across_tag_boundaries(): void
    {
        self::assertSame(2, $this->evaluate('word_count(text)', ['text' => '<p>foo</p><p>bar</p>']));
    }

    /**
     * @test
     */
    public function it_counts_zero_words_for_null(): void
    {
        self::assertSame(0, $this->evaluate('word_count(text)', ['text' => null]));
    }

    /**
     * @test
     */
    public function it_counts_unicode_words(): void
    {
        self::assertSame(2, $this->evaluate('word_count(text)', ['text' => 'Привет мир']));
    }

    /**
     * @test
     */
    public function it_counts_characters_with_multibyte_awareness(): void
    {
        self::assertSame(3, $this->evaluate('char_count(text)', ['text' => 'æøå']));
        self::assertSame(7, $this->evaluate('char_count(text)', ['text' => '<p>foo bar</p>']));
        self::assertSame(1, $this->evaluate('char_count(text)', ['text' => '&amp;']));
        self::assertSame(0, $this->evaluate('char_count(text)', ['text' => null]));
    }

    /**
     * @test
     */
    public function it_detects_blank_text(): void
    {
        self::assertTrue($this->evaluate('is_blank(text)', ['text' => null]));
        self::assertTrue($this->evaluate('is_blank(text)', ['text' => '<p>&nbsp;</p>']));
        self::assertFalse($this->evaluate('is_blank(text)', ['text' => 'x']));
    }

    /**
     * @test
     */
    public function it_changes_case_with_multibyte_awareness(): void
    {
        self::assertSame('æøå', $this->evaluate('lower(text)', ['text' => 'ÆØÅ']));
        self::assertSame('ÆØÅ', $this->evaluate('upper(text)', ['text' => 'æøå']));
        self::assertSame('', $this->evaluate('lower(text)', ['text' => null]));
    }

    /**
     * @test
     */
    public function it_trims(): void
    {
        self::assertSame('foo', $this->evaluate('trim(text)', ['text' => '  foo  ']));
    }

    /**
     * @test
     */
    public function it_checks_containment_case_insensitively(): void
    {
        self::assertTrue($this->evaluate('icontains(text, "FOO")', ['text' => 'foobar']));
        self::assertTrue($this->evaluate('icontains(text, "øst")', ['text' => 'Østerbro']));
        self::assertFalse($this->evaluate('icontains(text, "baz")', ['text' => 'foobar']));
        self::assertFalse($this->evaluate('icontains(text, "baz")', ['text' => null]));
    }

    /**
     * @test
     */
    public function it_checks_prefixes_and_suffixes(): void
    {
        self::assertTrue($this->evaluate('starts_with(text, "foo")', ['text' => 'foobar']));
        self::assertFalse($this->evaluate('starts_with(text, "bar")', ['text' => 'foobar']));
        self::assertTrue($this->evaluate('ends_with(text, "bar")', ['text' => 'foobar']));
        self::assertFalse($this->evaluate('ends_with(text, "foo")', ['text' => 'foobar']));
    }
}

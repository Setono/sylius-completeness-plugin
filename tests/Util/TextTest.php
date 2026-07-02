<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Util;

use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\Util\Text;

final class TextTest extends TestCase
{
    /**
     * @test
     */
    public function it_strips_html_and_collapses_whitespace(): void
    {
        self::assertSame('foo bar', Text::strip('<p>foo</p>  <p>bar</p>'));
        self::assertSame('foo bar', Text::strip("foo\n\n   bar"));
        self::assertSame('', Text::strip(null));
        self::assertSame('', Text::strip('<p>&nbsp;</p>'));
        self::assertSame('a & b', Text::strip('a &amp; b'));
    }

    /**
     * @test
     */
    public function it_detects_blank_strings(): void
    {
        self::assertTrue(Text::isBlank(null));
        self::assertTrue(Text::isBlank(''));
        self::assertTrue(Text::isBlank('  '));
        self::assertTrue(Text::isBlank('<br/>'));
        self::assertFalse(Text::isBlank('0'));
    }

    /**
     * @test
     */
    public function it_counts_words(): void
    {
        self::assertSame(0, Text::wordCount(null));
        self::assertSame(2, Text::wordCount('foo bar'));
        self::assertSame(2, Text::wordCount('<ul><li>foo</li><li>bar</li></ul>'));
        self::assertSame(3, Text::wordCount('Привет, мир 123'));
    }

    /**
     * @test
     */
    public function it_counts_characters(): void
    {
        self::assertSame(0, Text::charCount(null));
        self::assertSame(3, Text::charCount('æøå'));
        self::assertSame(7, Text::charCount('<p>foo bar</p>'));
    }

    /**
     * @test
     */
    public function it_coerces_values_to_strings(): void
    {
        self::assertSame('', Text::coerce(null));
        self::assertSame('foo', Text::coerce('foo'));
        self::assertSame('42', Text::coerce(42));

        $this->expectException(\InvalidArgumentException::class);
        Text::coerce([]);
    }
}

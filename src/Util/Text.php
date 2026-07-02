<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Util;

/**
 * Text helpers shared by checkers and expression functions. All methods are null tolerant,
 * HTML aware (descriptions come from a WYSIWYG) and Unicode safe
 */
final class Text
{
    private function __construct()
    {
    }

    /**
     * Coerces an expression argument to a string. Null becomes the empty string
     */
    public static function coerce(mixed $value): string
    {
        if (null === $value) {
            return '';
        }

        if (is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        throw new \InvalidArgumentException(sprintf('Cannot interpret a value of type %s as text', get_debug_type($value)));
    }

    /**
     * Strips HTML tags and entities and collapses whitespace
     */
    public static function strip(?string $text): string
    {
        if (null === $text || '' === $text) {
            return '';
        }

        // insert a space before every tag so '<p>foo</p><p>bar</p>' does not collapse to 'foobar'
        $text = str_replace('<', ' <', $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);
        $text = (string) preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    public static function isBlank(?string $text): bool
    {
        return '' === self::strip($text);
    }

    public static function wordCount(?string $text): int
    {
        $stripped = self::strip($text);
        if ('' === $stripped) {
            return 0;
        }

        return (int) preg_match_all('/[\p{L}\p{N}]+/u', $stripped);
    }

    public static function charCount(?string $text): int
    {
        return mb_strlen(self::strip($text));
    }
}

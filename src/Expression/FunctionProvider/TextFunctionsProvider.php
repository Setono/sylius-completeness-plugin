<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression\FunctionProvider;

use Setono\SyliusCompletenessPlugin\Util\Text;

final class TextFunctionsProvider extends FunctionProvider
{
    public function getFunctions(): array
    {
        return [
            $this->createFunction(
                'word_count',
                'word_count(text): int',
                'The number of words in the text after HTML is stripped (Unicode-safe; null counts as 0).',
                static fn (array $variables, mixed $text): int => Text::wordCount(Text::coerce($text)),
            ),
            $this->createFunction(
                'char_count',
                'char_count(text): int',
                'The number of characters in the text after HTML is stripped (Unicode-safe; null counts as 0).',
                static fn (array $variables, mixed $text): int => Text::charCount(Text::coerce($text)),
            ),
            $this->createFunction(
                'is_blank',
                'is_blank(text): bool',
                'True when the text is null or empty after HTML is stripped and trimmed.',
                static fn (array $variables, mixed $text): bool => Text::isBlank(Text::coerce($text)),
            ),
            $this->createFunction(
                'lower',
                'lower(text): string',
                'The text converted to lower case (Unicode-safe).',
                static fn (array $variables, mixed $text): string => mb_strtolower(Text::coerce($text)),
            ),
            $this->createFunction(
                'upper',
                'upper(text): string',
                'The text converted to upper case (Unicode-safe).',
                static fn (array $variables, mixed $text): string => mb_strtoupper(Text::coerce($text)),
            ),
            $this->createFunction(
                'trim',
                'trim(text): string',
                'The text with leading and trailing whitespace removed.',
                static fn (array $variables, mixed $text): string => trim(Text::coerce($text)),
            ),
            // 'contains' is a reserved (case sensitive) operator in Symfony ExpressionLanguage,
            // so the case insensitive variant is named icontains
            $this->createFunction(
                'icontains',
                'icontains(text, needle): bool',
                'True when the text contains the needle (case-insensitive).',
                static function (array $variables, mixed $text, mixed $needle): bool {
                    $needle = Text::coerce($needle);
                    if ('' === $needle) {
                        return true;
                    }

                    return false !== mb_stripos(Text::coerce($text), $needle);
                },
            ),
            $this->createFunction(
                'starts_with',
                'starts_with(text, prefix): bool',
                'True when the text starts with the given prefix (case-sensitive).',
                static fn (array $variables, mixed $text, mixed $prefix): bool => str_starts_with(Text::coerce($text), Text::coerce($prefix)),
            ),
            $this->createFunction(
                'ends_with',
                'ends_with(text, suffix): bool',
                'True when the text ends with the given suffix (case-sensitive).',
                static fn (array $variables, mixed $text, mixed $suffix): bool => str_ends_with(Text::coerce($text), Text::coerce($suffix)),
            ),
        ];
    }
}

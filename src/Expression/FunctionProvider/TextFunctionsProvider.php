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
                static fn (array $variables, mixed $text): int => Text::wordCount(Text::coerce($text)),
            ),
            $this->createFunction(
                'char_count',
                static fn (array $variables, mixed $text): int => Text::charCount(Text::coerce($text)),
            ),
            $this->createFunction(
                'is_blank',
                static fn (array $variables, mixed $text): bool => Text::isBlank(Text::coerce($text)),
            ),
            $this->createFunction(
                'lower',
                static fn (array $variables, mixed $text): string => mb_strtolower(Text::coerce($text)),
            ),
            $this->createFunction(
                'upper',
                static fn (array $variables, mixed $text): string => mb_strtoupper(Text::coerce($text)),
            ),
            $this->createFunction(
                'trim',
                static fn (array $variables, mixed $text): string => trim(Text::coerce($text)),
            ),
            // 'contains' is a reserved (case sensitive) operator in Symfony ExpressionLanguage,
            // so the case insensitive variant is named icontains
            $this->createFunction(
                'icontains',
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
                static fn (array $variables, mixed $text, mixed $prefix): bool => str_starts_with(Text::coerce($text), Text::coerce($prefix)),
            ),
            $this->createFunction(
                'ends_with',
                static fn (array $variables, mixed $text, mixed $suffix): bool => str_ends_with(Text::coerce($text), Text::coerce($suffix)),
            ),
        ];
    }
}

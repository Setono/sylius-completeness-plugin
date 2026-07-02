<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression\FunctionProvider;

use Setono\SyliusCompletenessPlugin\Util\Text;

final class CollectionFunctionsProvider extends FunctionProvider
{
    public function getFunctions(): array
    {
        return [
            $this->createFunction(
                'count',
                static fn (array $variables, mixed $value): int => self::count($value),
            ),
            $this->createFunction(
                'is_empty',
                static function (array $variables, mixed $value): bool {
                    if (is_string($value)) {
                        return Text::isBlank($value);
                    }

                    return 0 === self::count($value);
                },
            ),
            $this->createFunction(
                'min',
                static fn (array $variables, mixed $a, mixed $b): float|int => min(self::number($a, 'min'), self::number($b, 'min')),
            ),
            $this->createFunction(
                'max',
                static fn (array $variables, mixed $a, mixed $b): float|int => max(self::number($a, 'max'), self::number($b, 'max')),
            ),
            $this->createFunction(
                'between',
                static function (array $variables, mixed $value, mixed $low, mixed $high): bool {
                    $value = self::number($value, 'between');

                    return $value >= self::number($low, 'between') && $value <= self::number($high, 'between');
                },
            ),
        ];
    }

    private static function count(mixed $value): int
    {
        if (null === $value) {
            return 0;
        }

        if (is_countable($value)) {
            return \count($value);
        }

        if ($value instanceof \Traversable) {
            return iterator_count($value);
        }

        throw new \InvalidArgumentException(sprintf('Cannot count a value of type %s', get_debug_type($value)));
    }

    private static function number(mixed $value, string $function): float|int
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        throw new \InvalidArgumentException(sprintf(
            'The %s() function expects numeric arguments, got %s',
            $function,
            get_debug_type($value),
        ));
    }
}

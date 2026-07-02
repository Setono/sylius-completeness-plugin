<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Util;

final class Attributes
{
    private function __construct()
    {
    }

    /**
     * Returns true when an attribute value should be considered "not set": null, a blank string or an
     * empty list (select attributes store lists of option codes). Notice that false is a value (an
     * unchecked checkbox attribute is deliberately set to false)
     */
    public static function isEmpty(mixed $value): bool
    {
        if (null === $value) {
            return true;
        }

        if (is_string($value)) {
            return Text::isBlank($value);
        }

        if (is_array($value)) {
            return [] === $value;
        }

        return false;
    }
}

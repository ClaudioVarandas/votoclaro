<?php

namespace App\Services;

/**
 * The Parliament's JSON files are converted from XML, so an element that occurs once comes as an
 * object and one that repeats comes as a list. These helpers read both shapes the same way.
 */
class ParliamentJson
{
    /**
     * @return list<mixed>
     */
    public static function list(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value) && array_is_list($value)) {
            return $value;
        }

        return [$value];
    }

    /**
     * Joins a text that may come as one string or as a list of them.
     */
    public static function text(mixed $value, string $separator = '; '): ?string
    {
        $text = implode($separator, array_filter(
            array_map(fn (mixed $part): string => is_string($part) ? trim($part) : '', self::list($value)),
            fn (string $part): bool => $part !== '',
        ));

        return $text === '' ? null : $text;
    }
}

<?php

namespace App\Services\Mappers;

class MapperRegistry
{
    private static array $mappers = [];

    public static function clear(): void
    {
        self::$mappers = [];
    }

    public static function register(string $class, callable $matcher): void
    {
        self::$mappers[] = [
            'class' => $class,
            'matcher' => $matcher
        ];
    }

    public static function resolve(string $text): string
    {
        foreach (self::$mappers as $entry) {
            if (($entry['matcher'])($text)) {
                return $entry['class'];
            }
        }

        return GenericContractMapper::class;
    }
}
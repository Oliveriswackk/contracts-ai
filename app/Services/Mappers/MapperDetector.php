<?php

namespace App\Services\Mappers;

class MapperDetector
{
    public static function detect(string $text): string
    {
        return MapperRegistry::resolve($text);
    }
}
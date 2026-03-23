<?php

namespace App\Services\Mappers;

class MapperFactory
{
    public static function make(string $text): MapperInterface
    {
        $class = MapperDetector::detect($text);

        return new $class();
    }
}
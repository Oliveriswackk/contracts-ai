<?php

namespace App\Services\Mappers;

class MapperFactory
{
    public static function make(string $text): MapperInterface
    {
        // Detección real (luego)

        return new ContractMapper();
    }
}
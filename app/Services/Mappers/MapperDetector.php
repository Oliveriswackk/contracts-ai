<?php

namespace App\Services\Mappers;

class MapperDetector
{
    public static function detect(string $text): string
    {

        // regla 1
        if (str_contains($text, 'SESEA/')) {
            return ContractMapper::class;
        }

        // regla 2
        if (str_contains($text, 'CONTRATO')) {
            return GenericContractMapper::class;
        }

        // fallback (Devuelve class Name, no instancia !!!)
        return GenericContractMapper::class;
    }
}
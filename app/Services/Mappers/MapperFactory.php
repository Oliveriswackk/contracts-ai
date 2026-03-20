<?php

namespace App\Services\Mappers;

class MapperFactory
{
    public static function make(string $text): MapperInterface
    {

        // Regla 1 — contrato SESEA
        if (str_contains($text, 'SESEA/')) {
            echo "Usando ContratMapper\n";
            return new ContractMapper();
        }

        // Regla 2 — fallback
        echo "Usando GenericContractmapper\n";
        return new GenericContractMapper();
    }
}
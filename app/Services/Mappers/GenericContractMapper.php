<?php

namespace App\Services\Mappers;

class GenericContractMapper implements MapperInterface
{
    public function map(string $text): array
    {
        return [
            'numero' => null,
            'tipo' => null,
            'proveedor' => null,
            'rfc_proveedor' => null,
            'dependencia' => null,
            'monto' => null,
            'fecha_firma' => null,
            'fecha_inicio' => null,
            'fecha_fin' => null,
        ];
    }
}
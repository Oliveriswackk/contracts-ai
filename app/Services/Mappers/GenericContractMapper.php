<?php

namespace App\Services\Mappers;

class GenericContractMapper extends BaseContractMapper implements MapperInterface
{
    public function map(string $text): array
    {
        return [
            'numero' => $this->wrap(null),
            'tipo' => [
                'value' => null,
                'confidence' => 0.0,
                'sources' => ['generic']
            ],
            'proveedor' => $this->wrap(null),
            'rfc_proveedor' => $this->wrap(null),
            'dependencia' => $this->wrap(null),
            'monto' => $this->wrap(null),
            'fecha_firma' => $this->wrap(null),
            'fecha_inicio' => $this->wrap(null),
            'fecha_fin' => $this->wrap(null),
        ];
    }
}
<?php

namespace App\Services\Mappers;

use App\Services\Mappers\BaseContractMapper;

class AdquisicionBienesBISMapper extends BaseContractMapper implements MapperInterface
{
    public function map(string $text): array
    {
        return [
            'numero' => $this->extractNumero($text),
            'tipo' => 'ADQUISICION_BIENES_BIS',
            'proveedor' => $this->extractProveedor($text),
            'rfc_proveedor' => $this->extractRFC($text),
            'dependencia' => $this->extractDependencia($text),
            'monto' => $this->extractMonto($text),
            'fecha_firma' => $this->extractFechaFirma($text),
            'fecha_inicio' => $this->extractFechaInicio($text),
            'fecha_fin' => $this->extractFechaFin($text),
        ];
    }
}
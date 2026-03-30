<?php

namespace App\Services\Mappers;

class PrestacionServiciosMapper extends BaseContractMapper implements MapperInterface
{
    public function map(string $text): array
    {
        return [
            'numero' => $this->wrap($this->extractNumero($text)),
            'tipo' => [
                'value' => 'PRESTACION_SERVICIOS',
                'confidence' => 1.0,
                'sources' => ['static']
            ],
            'proveedor' => $this->wrap($this->extractProveedor($text), $text),
            'rfc_proveedor' => $this->wrap($this->extractRFC($text)),
            'dependencia' => $this->wrap($this->extractDependencia($text)),
            'monto' => $this->wrap($this->extractMonto($text), $text),
            'fecha_firma' => $this->wrap($this->extractFechaFirma($text)),
            'fecha_inicio' => $this->wrap($this->extractFechaInicio($text)),
            'fecha_fin' => $this->wrap($this->extractFechaFin($text)),
        ];
    }
}
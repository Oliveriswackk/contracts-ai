<?php

namespace App\Services\DTO;

class ContractDTO
{
    public ?string $numero;
    public ?string $tipo;
    public ?string $dependencia;
    public ?string $proveedor;
    public ?string $rfcProveedor;
    public ?float $monto;
    public ?string $moneda;
    public ?string $fechaFirma;
    public ?string $fechaInicio;
    public ?string $fechaFin;

    public function __construct(array $data)
    {
        $this->numero = $data['numero'] ?? null;
        $this->tipo = $data['tipo'] ?? null;
        $this->dependencia = $data['dependencia'] ?? null;
        $this->proveedor = $data['proveedor'] ?? null;
        $this->rfcProveedor = $data['rfc_proveedor'] ?? null;
        $this->monto = isset($data['monto']) ? (float)$data['monto'] : null;
        $this->moneda = $data['moneda'] ?? 'MXN';
        $this->fechaFirma = $data['fecha_firma'] ?? null;
        $this->fechaInicio = $data['fecha_inicio'] ?? null;
        $this->fechaFin = $data['fecha_fin'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'numero' => $this->numero,
            'tipo' => $this->tipo,
            'dependencia' => $this->dependencia,
            'proveedor' => $this->proveedor,
            'rfc_proveedor' => $this->rfcProveedor,
            'monto' => $this->monto,
            'moneda' => $this->moneda,
            'fecha_firma' => $this->fechaFirma,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin' => $this->fechaFin,
        ];
    }
}
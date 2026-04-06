<?php

namespace App\Services;

use App\Services\DTO\ContractDTO;

class ContractNormalizer
{
    /**
     * PRINCIPIO:
     * - No inferir
     * - No rellenar
     * - Solo aceptar lo confiable
     */

    private const CONFIDENCE_THRESHOLD = 0.7;

    public function normalize(array $data): ContractDTO
    {
        $numero = $this->acceptIfConfident($data['numero'] ?? null);
        $tipo = $this->acceptIfConfident($data['tipo'] ?? null);
        $proveedor = $this->acceptIfConfident($data['proveedor'] ?? null);
        $rfc = $this->acceptIfConfident($data['rfc_proveedor'] ?? null);
        $dependencia = $this->acceptIfConfident($data['dependencia'] ?? null);
        $monto = $this->acceptIfConfident($data['monto'] ?? null);
        $fechaFirma = $this->acceptIfConfident($data['fecha_firma'] ?? null);
        $fechaInicio = $this->acceptIfConfident($data['fecha_inicio'] ?? null);
        $fechaFin = $this->acceptIfConfident($data['fecha_fin'] ?? null);

        $tipo = $this->normalizeTipo($tipo);
        $proveedor = $this->normalizeProveedor($proveedor);
        $rfc = $this->validateRfc($rfc);
        $dependencia = $this->normalizeDependencia($dependencia);
        $monto = $this->normalizeMonto($monto);

        $moneda = $monto !== null ? 'MXN' : null;

        $fechaFirma = $this->formatFecha($fechaFirma);
        $fechaInicio = $this->formatFecha($fechaInicio);
        $fechaFin = $this->formatFecha($fechaFin);

        if ($fechaInicio && $fechaFin && $fechaFin < $fechaInicio) {
            $fechaFin = null;
        }

        return new ContractDTO([
            'numero' => $numero,
            'tipo' => $tipo,
            'dependencia' => $dependencia,
            'proveedor' => $proveedor,
            'rfc_proveedor' => $rfc,
            'monto' => $monto,
            'moneda' => $moneda,
            'fecha_firma' => $fechaFirma,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ]);
    }

    private function acceptIfConfident($field)
    {
        if (!is_array($field)) {
            return $field;
        }

        $confidence = $field['confidence'] ?? 0.0;

        return $confidence >= self::CONFIDENCE_THRESHOLD
            ? ($field['value'] ?? null)
            : null;
    }

    private function normalizeTipo(?string $tipo): ?string
    {
        if (!$tipo) return null;

        $tipo = strtoupper($tipo);

        if (str_contains($tipo, 'SERVICIO')) {
            return 'SERVICIOS';
        }

        if (str_contains($tipo, 'ARRENDAMIENTO')) {
            return 'ARRENDAMIENTO';
        }

        if (str_contains($tipo, 'ADQUISICION')) {
            return 'ADQUISICION_BIENES';
        }

        return $tipo;
    }

    private function normalizeProveedor(?string $p): ?string
    {
        return $p ? trim($p) : null;
    }

    private function normalizeDependencia(?string $d): ?string
    {
        return $d ? trim($d) : null;
    }

    private function normalizeMonto(?string $m): ?float
    {
        if (!$m) return null;

        $m = str_replace(',', '', $m);

        if (!is_numeric($m)) {
            return null;
        }

        $m = (float) $m;

        return $m > 0 ? $m : null;
    }

    private function formatFecha(?string $fecha): ?string
    {
        if (!$fecha) return null;

        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $fecha, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        if (preg_match(
            '/(\d{1,2}) de ([a-záéíóúñ]+)(?: del año| de)? (\d{4})/iu',
            $fecha,
            $m
        )) {

            $meses = [
                'enero' => '01',
                'febrero' => '02',
                'marzo' => '03',
                'abril' => '04',
                'mayo' => '05',
                'junio' => '06',
                'julio' => '07',
                'agosto' => '08',
                'septiembre' => '09',
                'octubre' => '10',
                'noviembre' => '11',
                'diciembre' => '12',
            ];

            $dia = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $mes = $meses[strtolower($m[2])] ?? null;
            $anio = $m[3];

            return $mes ? "$anio-$mes-$dia" : null;
        }

        return null;
    }

    private function validateRfc(?string $rfc): ?string
    {
        if (!$rfc) return null;

        $rfc = strtoupper(trim($rfc));

        if (!preg_match('/^[A-Z&Ñ]{3,4}\d{6}[A-Z0-9]{3}$/', $rfc)) {
            return null;
        }

        return $rfc;
    }
}
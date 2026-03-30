<?php

namespace App\Services;

class ContractNormalizer
{
    /**
     * ContractNormalizer
     *
     * PRINCIPIO:
     * - No inferir
     * - No rellenar
     * - Solo aceptar lo confiable
     *
     * Este es el último filtro antes de salida.
     */

    public function normalize(array $data): array
    {
        $out = [];

        $out['numero'] = $data['numero']['value'] ?? null;

        $out['tipo'] = $this->normalizeTipo($data['tipo']['value'] ?? null);

        $out['proveedor'] = $this->normalizeProveedor($data['proveedor']['value'] ?? null);

        $out['rfc_proveedor'] = $this->validateRfc($data['rfc_proveedor']['value'] ?? null);

        $out['dependencia'] = $this->normalizeDependencia($data['dependencia']['value'] ?? null);

        $monto = $this->normalizeMonto($data['monto']['value'] ?? null);

        $out['monto'] = $monto;

        /*
        * MONEDA:
        * Sistema cerrado a MXN por definición de dominio.
        * No se realiza detección automática.
        */
        $out['moneda'] = $monto !== null ? 'MXN' : null;

        $out['fecha_firma'] = $this->formatFecha($data['fecha_firma']['value'] ?? null);

        $out['fecha_inicio'] = $this->formatFecha($data['fecha_inicio']['value'] ?? null);

        $out['fecha_fin'] = $this->formatFecha($data['fecha_fin']['value'] ?? null);

        if ($out['fecha_inicio'] && $out['fecha_fin'] && $out['fecha_fin'] < $out['fecha_inicio']) {
            $out['fecha_fin'] = null;
        }

        return $out;
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
        if (!$fecha) {
            return null;
        }

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
        if (!$rfc) {
            return null;
        }

        $rfc = strtoupper(trim($rfc));

        if (!preg_match('/^[A-Z&Ñ]{3,4}\d{6}[A-Z0-9]{3}$/', $rfc)) {
            return null;
        }

        return $rfc;
    }
}
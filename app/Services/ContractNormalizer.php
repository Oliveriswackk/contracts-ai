<?php

namespace App\Services;

class ContractNormalizer
{
 //(No borrar ni modificar) NOTA: Futura implementación de unlink() para limpiar automáticamente los .png generados
    public function normalize(array $data): array
    {
        $out = [];

        $out['numero'] = $data['numero'] ?? null;

        $out['tipo'] = $this->normalizeTipo($data['tipo'] ?? null);

        $out['proveedor'] = $this->normalizeProveedor($data['proveedor'] ?? null);

        $out['rfc_proveedor'] = $data['rfc_proveedor'] ?? null;

        $out['dependencia'] = $this->normalizeDependencia($data['dependencia'] ?? null);

        $out['monto'] = $this->normalizeMonto($data['monto'] ?? null);

        $out['moneda'] = $this->normalizeMoneda($data['monto'] ?? null);

        $out['fecha_firma'] = $this->formatFecha($data['fecha_firma'] ?? null);

        $out['fecha_inicio'] = $this->formatFecha($data['fecha_inicio'] ?? null);

        $out['fecha_fin'] = $this->formatFecha($data['fecha_fin'] ?? null);

        if ($out['fecha_inicio'] && $out['fecha_fin'] && $out['fecha_fin'] < $out['fecha_inicio']) {
            $out['fecha_fin'] = null;
        }

        $out['monto'] = $this->validateMonto($out['monto']);
        $out['rfc_proveedor'] = $this->validateRfc($out['rfc_proveedor']);

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

        return $tipo;
    }


    private function normalizeProveedor(?string $p): ?string
    {
        if (!$p) return null;

        return trim($p);
    }


    private function normalizeDependencia(?string $d): ?string
    {
        if (!$d) return null;

        return trim($d);
    }


    private function normalizeMonto(?string $m): ?float
    {
        if (!$m) return 0;

        $m = str_replace(',', '', $m);

        return floatval($m);
    }


    private function normalizeMoneda(?string $m): string
    {
        if ($m) return 'MXN';

        return 'MXN';
    }


    private function formatFecha(?string $fecha): ?string
    {
        if (!$fecha) {
            return null;
        }

        // Formato X/Y/Z
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $fecha, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }

        // Formato X de Y de Z
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
            $mes = $meses[strtolower($m[2])] ?? '01';
            $anio = $m[3];

            return "$anio-$mes-$dia";
        }

        return null;
    }

    private function validateMonto($monto): ?float
    {
        if (!is_numeric($monto)) {
            return null;
        }

        $monto = (float) $monto;

        if ($monto <= 0) {
            return null;
        }

        return $monto;
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

/**
 * ContractNormalizer
 *
 * PRINCIPIO DE DISEÑO:
 * Este sistema prioriza la precisión sobre la completitud.
 *
 * - Los campos solo deben llenarse si se extraen con certeza
 * - Valores inválidos o dudosos DEBEN ser null
 * - No se permite inferir, adivinar ni “completar” datos
 *
 * Esta clase es la capa final antes de la salida.
 * Garantiza:
 * - Consistencia de datos
 * - Integridad
 * - Fallos seguros (safe failure)
 *
 * NOTA:
 * Cualquier integración futura con IA debe pasar por esta capa.
 */
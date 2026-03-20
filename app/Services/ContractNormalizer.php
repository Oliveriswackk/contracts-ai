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


    private function formatFecha(?string $fechaTexto): ?string
    {
        if (!$fechaTexto) return null;

        $meses = [
            'enero'=>'01','febrero'=>'02','marzo'=>'03','abril'=>'04','mayo'=>'05','junio'=>'06',
            'julio'=>'07','agosto'=>'08','septiembre'=>'09','octubre'=>'10','noviembre'=>'11','diciembre'=>'12'
        ];

        if (preg_match('/(\d{1,2}) de ([a-záéíóúñ]+) (?:del año |de )?(\d{4})/iu', $fechaTexto, $m)) {

            $dia = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $mes = $meses[strtolower($m[2])] ?? '01';
            $anio = $m[3];

            return "$anio-$mes-$dia";
        }

        return null;
    }
}
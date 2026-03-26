<?php

namespace App\Services\Mappers;

class ContractMapper extends BaseContractMapper implements MapperInterface
{


    public function map(string $text): array
    {
        $text = preg_replace('/\s+/', ' ', $text);

        return [

            'numero' => $this->extractNumero($text),
            'tipo' => $this->extractTipo($text),
            'proveedor' => $this->extractProveedor($text),
            'rfc_proveedor' => $this->extractRFC($text),
            'dependencia' => $this->extractDependencia($text),
            'monto' => $this->extractMonto($text),
            'fecha_firma' => $this->extractFechaFirma($text),
            'fecha_inicio' => $this->extractFechaInicio($text),
            'fecha_fin' => $this->extractFechaFin($text),

        ];
    }


    // Se queda aqui al depender del tipo de contrato
    private function extractTipo(string $text): ?string
    {
        if (preg_match('/CONTRATO DE\s+(.*?)(?:NÚMERO|$)/iu', $text, $m)) {
            return trim($m[1]);
        }

        if (preg_match('/CONTRATO DE\s+(.*?)(?:$|\n)/iu', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }
}
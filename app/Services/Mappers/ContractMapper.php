<?php

namespace App\Services\Mappers;

class ContractMapper
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


    private function extractNumero(string $text): ?string
    {
        if (preg_match('/SESEA\/[A-Z]+\/\d+\/\d+/', $text, $m)) {
            return $m[0];
        }
        return null;
    }


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


    private function extractProveedor(string $text): ?string
    {
        if (preg_match('/[A-ZÁÉÍÓÚÑ ]+(S\.A\. DE C\.V\.|S\. DE R\.L\. DE C\.V\.|A\.C\.)/u', $text, $m)) {
            return trim($m[0]);
        }

        return null;
    }


    private function extractRFC(string $text): ?string
    {
        if (preg_match('/[A-Z]{3,4}\d{6}[A-Z0-9]{3}/', $text, $m)) {
            return $m[0];
        }

        return null;
    }


    private function extractDependencia(string $text): ?string
    {
        
        if (preg_match('/Secretaría\s+(.+?)(?:acredita|,|\.|\d)/u', $text, $m)) {
            return trim($m[1]);
        }
            
        if (preg_match('/Secretaría\s+([A-ZÁÉÍÓÚÑóa-z\s]+?)(?=\s+(CONTRATO|OBJETO|R\.F\.C\.|[0-9]))/u', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }


    private function extractMonto(string $text): ?string
    {
        if (preg_match('/\$ ?([\d,]+\.\d+)/', $text, $m)) {
            return $m[1]; // string, no float aún
        }

        return null;
    }


    private function extractFechaFirma(string $text): ?string
    {
        if (preg_match('/(\d{1,2} de [a-záéíóúñ]+ del año \d{4})/iu', $text, $m)) {
            return $m[1];
        }

        return null;
    }


    private function extractFechaInicio(string $text): ?string
    {
        if (preg_match_all('/(\d{1,2} de [a-záéíóúñ]+ (?:del año |de )?\d{4})/iu', $text, $matches)) {
            return $matches[1][0] ?? null;
        }

        return null;
    }


    private function extractFechaFin(string $text): ?string
    {
        if (preg_match_all('/(\d{1,2} de [a-záéíóúñ]+ (?:del año |de )?\d{4})/iu', $text, $matches)) {
            return end($matches[1]) ?: null;
        }

        return null;
    }
}
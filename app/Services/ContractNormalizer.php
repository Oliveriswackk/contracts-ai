<?php

namespace App\Services;

class ContractNormalizer
{
    //(No borrar ni modificar) NOTA: Futura implementación de unlink() para limpiar automáticamente los .png generados

    public function normalize(string $text): array
    {
        // Limpiar espacios extra
        $text = preg_replace('/\s+/', ' ', $text);

        $data = [];

        // Extracción dinámica
        $data['numero'] = $this->extractNumero($text);
        $data['tipo'] = $this->extractTipo($text);
        $data['proveedor'] = $this->extractProveedor($text);
        $data['rfc_proveedor'] = $this->extractRFC($text);
        $data['dependencia'] = $this->extractDependencia($text);
        $data['monto'] = $this->extractMonto($text);
        $data['moneda'] = $this->extractMoneda($text);
        $data['fecha_firma'] = $this->extractFechaFirma($text);
        $data['fecha_inicio'] = $this->extractFechaInicio($text);
        $data['fecha_fin'] = $this->extractFechaFin($text);

        return $data;
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
        return null;
    }


    private function extractProveedor(string $text): ?string
    {
        if (preg_match('/[A-ZÁÉÍÓÚÑ ]+S\.A\. DE C\.V\./u', $text, $m)) {
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
        
        // Si falla, fallback más general hasta palabras típicas que marcan el final
        if (preg_match('/Secretaría\s+(.+?)(?:acredita|,|\.|\d)/u', $text, $m)) {
            return trim($m[1]);
        }
            
        // Primero intento con delimitadores más precisos
        if (preg_match('/Secretaría\s+([A-ZÁÉÍÓÚÑ][A-Za-zÁÉÍÓÚÑáéíóúñ\s]*?)(?=\s+(CONTRATO|OBJETO|R\.F\.C\.|[0-9]))/u', $text, $m)) {
            return trim($m[1]);
        }
        
        return null;
    }


    private function extractMonto(string $text): ?float
    {
        if (preg_match('/\$ ?([\d,]+\.\d+)/', $text, $m)) {
            return floatval(str_replace(',', '', $m[1]));
        }
        return null;
    }


    private function extractMoneda(string $text): ?string
    {
        if (strpos($text, '$') !== false) {
            return 'MXN';
        }
        return null;
    }


    private function extractFechaFirma(string $text): ?string
    {
        if (preg_match('/(\d{1,2} de [a-záéíóúñ]+ del año \d{4})/iu', $text, $m)) {
            // Convertir a formato YYYY-MM-DD
            return $this->formatFecha($m[1]);
        }
        return null;
    }


    private function extractFechaInicio(string $text): ?string
    {
        // Primera fecha en el texto
        if (preg_match_all('/(\d{1,2} de [a-záéíóúñ]+ (?:del año |de )?\d{4})/iu', $text, $matches)) {
            return $this->formatFecha($matches[1][0]);
        }
        return null;
    }


    private function extractFechaFin(string $text): ?string
    {
        // Última fecha en el texto
        if (preg_match_all('/(\d{1,2} de [a-záéíóúñ]+ (?:del año |de )?\d{4})/iu', $text, $matches)) {
            return $this->formatFecha(end($matches[1]));
        }
        return null;
    }


    private function formatFecha(string $fechaTexto): ?string
    {
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
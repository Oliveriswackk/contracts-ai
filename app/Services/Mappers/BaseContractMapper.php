<?php

namespace App\Services\Mappers;

use Illuminate\Support\Facades\Log;

/**
 * BaseContractMapper
 *
 * SOLO CAPA DE EXTRACCIÓN.
 * 
 * Responsabilidades:
 * - Extraer datos crudos del texto usando patrones deterministas (regex)
 *
 * Restricciones:
 * - No validar datos
 * - No corregir datos
 * - No asumir valores si no están claros
 *
 * Si no se puede extraer un dato, regresar null.
 *
 * La validación final la realiza ContractNormalizer.
 */

abstract class BaseContractMapper
{
    abstract public function map(string $text): array;

    protected function wrap($value, $text = null): array
    {
        return [
            'value' => $value,
            'confidence' => $this->calculateConfidence($value, $text),
            'sources' => $value !== null ? ['regex'] : []
        ];
    }


    protected function calculateConfidence($value, $text = null): float
    {
        if (is_null($value)) return 0.0;

        $score = 0.6;

        if (!empty($value)) $score += 0.2;

        if ($text && str_contains($text, (string)$value)) {
            $score += 0.2;
        }

        return min($score, 1.0);
    }


    protected function extractNumero(string $text): ?string
    {
        if (preg_match('/SESEA\/[A-Z]+\/\d+\/\d+/', $text, $m)) {
            return $m[0];
        }
        return null;
    }


    protected function extractProveedor(string $text): ?string
    {
        // Busca explícitamente después de "denominada" o "moral denominada"
        // que es el patrón más limpio en estos contratos
        if (preg_match(
            '/(?:moral\s+denominada|adjudicada\s+a\s+(?:la\s+)?(?:persona\s+moral\s+denominada\s+)?)\s*([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s]+?)\s*(S\.A\.\s*DE\s*C\.V\.|S\.\s*DE\s*R\.L\.\s*DE\s*C\.V\.|A\.C\.)/iu',
            $text,
            $m
        )) {
            return trim($m[1]) . ' ' . trim($m[2]);
        }

        // Fallback: busca el nombre justo antes del sufijo societario,
        // pero excluye palabras que no son parte del nombre
        if (preg_match(
            '/\b((?!(?:LA|DE|EL|PERSONA|MORAL|LEGAL|REPRESENTANTE|PARTE|CONDUCTO|OTRA)\b)[A-ZÁÉÍÓÚÑ]+(?:\s+(?!(?:LA|DE|EL|PERSONA|MORAL|LEGAL|REPRESENTANTE|PARTE|CONDUCTO|OTRA)\b)[A-ZÁÉÍÓÚÑ]+){0,5})\s+(S\.A\. DE C\.V\.|S\. DE R\.L\. DE C\.V\.|A\.C\.)/u',
            $text,
            $m
        )) {
            return trim($m[1]) . ' ' . trim($m[2]);
        }

        return null;
    }


    protected function extractRFC(string $text): ?string
    {
        // Busca RFC cercano al bloque del proveedor/prestador.
        if (preg_match_all('/\b([A-Z]{3,4}\d{6}[A-Z0-9]{3})\b/u', $text, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $entry) {
                $rfc = $entry[0];
                $offset = $entry[1];

                $start = max(0, $offset - 260);
                $context = mb_substr($text, $start, $offset - $start);

                if (
                    preg_match('/(?:PROVEEDOR|PRESTADOR|DECLARA\s+“?EL\s+(?:PROVEEDOR|PRESTADOR)|II\.\s*-)/iu', $context)
                    && !preg_match('/SECRETAR[ÍI]A\s+EJECUTIVA/iu', $context)
                ) {
                    return $rfc;
                }
            }

            $unique = array_values(array_unique(array_map(fn($m) => $m[0], $matches[1])));

            foreach ($unique as $rfc) {
                if (!preg_match('/^SES\d{6}[A-Z0-9]{3}$/', $rfc)) {
                    return $rfc;
                }
            }

            return $unique[0] ?? null;
        }

        return null;
    }

    
    protected function extractDependencia(string $text): ?string
    {
        if (preg_match(
            '/Secretar[íi]a\s+Ejecutiva\s+del\s+Sistema\s+Estatal\s+Anticorrupci[oó]n/ui',
            $text
        )) {
            return 'Secretaría Ejecutiva del Sistema Estatal Anticorrupción';
        }

        if (preg_match('/Secretaría\s+(.+?)(?:acredita|,|\.|\d)/u', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }


    protected function extractMonto(string $text): ?string
    {
        // Prioridad 1: cláusula SEGUNDA (monto contractual real)
        if (preg_match(
            '/SEGUNDA[^$]{0,150}\$\s?([\d,]+\.\d{2})/u',
            $text,
            $m
        )) {
            return str_replace(',', '', $m[1]);
        }

        // Prioridad 2: "monto" o "importe" + cantidad
        if (preg_match(
            '/(?:monto|importe)[^$]{0,80}\$\s?([\d,]+\.\d{2})/iu',
            $text,
            $m
        )) {
            return str_replace(',', '', $m[1]);
        }

        // Fallback genérico
        if (preg_match('/\$\s?([\d,]+\.\d{2})/', $text, $m)) {
            return str_replace(',', '', $m[1]);
        }

        return null;
    }


    protected function extractFechaFirma(string $text): ?string
    {
        // Patrón 1: "A LOS 06 DÍAS DEL MES DE NOVIEMBRE DE 2023"
        // Este aparece en el pie de firmas y el OCR lo captura limpio
        if (preg_match(
            '/A\s+LOS\s+(\d{1,2})\s+D[ÍI]AS?\s+DEL\s+MES\s+DE\s+([A-ZÁÉÍÓÚÑ]+)\s+DE\s+(\d{4})/iu',
            $text,
            $m
        )) {
            return "0{$m[1]} de " . mb_strtolower($m[2]) . " de {$m[3]}";
        }

        // Patrón 2: "al día 06 de noviembre de YYYY" (cuando el OCR no distorsiona el año)
        if (preg_match(
            '/al\s+d[íi]a\s+(\d{1,2})\s+de\s+([a-záéíóúñ]+)\s+de\s+(\d{4})/iu',
            $text,
            $m
        )) {
            return "{$m[1]} de {$m[2]} de {$m[3]}";
        }

        // Patrón 3: última fecha "DD de mes de YYYY" en el documento
        // (evita fechas del cuerpo del contrato tomando la última)
        if (preg_match_all(
            '/(\d{1,2})\s+de\s+([a-záéíóúñ]+)\s+de\s+(\d{4})/iu',
            $text,
            $m,
            PREG_SET_ORDER
        )) {
            $last = end($m);
            return "{$last[1]} de {$last[2]} de {$last[3]}";
        }

        return null;
    }

    
    protected function extractFechaInicio(string $text): ?string
    {
        if (preg_match_all(
            '/(\d{2}\/\d{2}\/\d{4})\s*[-–—]\s*(\d{2}\/\d{2}\/\d{4})/',
            $text,
            $m,
            PREG_SET_ORDER
        )) {

            foreach ($m as $match) {

                $inicio = $match[1];
                $fin = $match[2];

                $anioInicio = (int) substr($inicio, 6, 4);
                $anioFin = (int) substr($fin, 6, 4);

                // contrato típico: vigencia anual
                if ($anioFin >= $anioInicio) {
                    return $inicio;
                }
            }

            // fallback → primer rango encontrado
            return $m[0][1];
        }

        if (preg_match(
            '/(\d{1,2}\s+de\s+[a-záéíóúñ]+\s+de\s+\d{4})\s+(?:a|al|hasta)\s+(\d{1,2}\s+de\s+[a-záéíóúñ]+\s+de\s+\d{4})/iu',
            $text,
            $m
        )) {
            return trim($m[1]);
        }

        if (preg_match('/a\s+partir\s+de\s+la\s+fecha\s+de\s+su\s+firma/iu', $text)) {
            return $this->extractFechaFirma($text);
        }

        return null;
    }

    
    protected function extractFechaFin(string $text): ?string
    {
        if (preg_match_all(
            '/(\d{2}\/\d{2}\/\d{4})\s*[-–—]\s*(\d{2}\/\d{2}\/\d{4})/',
            $text,
            $m,
            PREG_SET_ORDER
        )) {

            foreach ($m as $match) {

                $inicio = $match[1];
                $fin = $match[2];

                $anioInicio = (int) substr($inicio, 6, 4);
                $anioFin = (int) substr($fin, 6, 4);

                if ($anioFin >= $anioInicio) {
                    return $fin;
                }
            }

            return $m[0][2];
        }

        if (preg_match(
            '/(\d{1,2}\s+de\s+[a-záéíóúñ]+\s+de\s+\d{4})\s+(?:a|al|hasta)\s+(\d{1,2}\s+de\s+[a-záéíóúñ]+\s+de\s+\d{4})/iu',
            $text,
            $m
        )) {
            return trim($m[2]);
        }

        if (preg_match('/hasta\s+el\s+d[íi]a\s+(\d{1,2}\s+de\s+[a-záéíóúñ]+\s+de\s+\d{4})/iu', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }
}
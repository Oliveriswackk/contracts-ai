<?php

namespace App\Services\Mappers;

use Illuminate\Support\Facades\Log;

abstract class BaseContractMapper
{
    abstract public function map(string $text): array;

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
        // Busca RFC en el bloque de declaraciones del PROVEEDOR (sección II)
        if (preg_match(
            '/(?:II\.|PROVEEDOR|proveedor)[^R]{0,300}Contribuyentes[:\s]+([A-Z]{3,4}\d{6}[A-Z0-9]{3})/us',
            $text,
            $m
        )) {
            return $m[1];
        }

        // Fallback: toma el segundo RFC único del documento
        if (preg_match_all('/\b([A-Z]{3,4}\d{6}[A-Z0-9]{3})\b/', $text, $m)) {
            $unique = array_values(array_unique($m[1]));
            return $unique[1] ?? $unique[0] ?? null;
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

        return null;
    }
}
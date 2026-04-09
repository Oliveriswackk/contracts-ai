<?php

namespace App\Support;

class DateParser
{
    public static function normalizeText(string $text): string
    {
        $text = strtolower($text);

        // correcciones típicas OCR
        $replacements = [
            'mi eíntitrés' => '2023',
            'eíntitrés' => '2023',
            'veintitrés' => '2023',
            'dos mil veintitrés' => '2023',
            'dos mil eíntitrés' => '2023',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }


    public static function extractSignatureBlock(string $text): string
    {
        $pos = strripos($text, 'previa lectura');

        if ($pos !== false) {
            return substr($text, $pos);
        }

        return $text;
    }


    public static function parseSpanishDate(string $text): ?string
    {
        $months = [
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

        // === FORMATO 1: "06 de noviembre de 2023"
        if (preg_match('/(\d{1,2})\s*(?:de)?\s*(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)\s*(?:de)?\s*(\d{4})/i', $text, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = $months[strtolower($m[2])] ?? null;
            $year = $m[3];

            return $month ? "$year-$month-$day" : null;
        }

        // === FORMATO 2: "06 DÍAS DEL MES DE NOVIEMBRE DE 2023"
        if (preg_match('/(\d{1,2})\s*d[ií]as?\s*del\s*mes\s*de\s*(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)\s*de\s*(\d{4})/i', $text, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = $months[strtolower($m[2])] ?? null;
            $year = $m[3];

            return $month ? "$year-$month-$day" : null;
        }

        return null;
    }
}
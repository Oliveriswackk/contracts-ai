<?php

namespace App\Services\Text;

class TextPreprocessor
{
    public static function normalize(string $text): string
    {
        $original = $text;

        // Normalizar saltos de línea
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Unificar espacios múltiples
        $text = preg_replace('/[ \t]+/u', ' ', $text);

        // Normalizar guiones
        $text = str_replace(['–', '—'], '-', $text);

        // Corregir "19 -49" → "19 - 49"
        $text = preg_replace('/(\d)\s*-\s*(\d)/u', '$1 - $2', $text);

        // Convertir saltos de línea en espacio
        $text = preg_replace('/\s+/u', ' ', $text);     

        // Debug útil
        if ($original !== $text) {
            file_put_contents(
                storage_path('app/debug_preprocessed.txt'),
                "===== BEFORE =====\n\n" .
                mb_substr($original, 0, 2000) .
                "\n\n===== AFTER =====\n\n" .
                mb_substr($text, 0, 2000)
            );
        }

        return trim($text);
    }
}
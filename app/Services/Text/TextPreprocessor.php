<?php

namespace App\Services\Text;

class TextPreprocessor
{
    public static function normalize(string $text): string
    {
        // Limitar tamaño ANTES de cualquier cosa (clave)
        if (strlen($text) > 100000) {
            $text = substr($text, 0, 100000);
        }

        // Normalizar encoding (seguro para texto corrupto)
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = iconv('UTF-8', 'UTF-8//IGNORE', $text);

        // Dividir en chunks pequeños
        $chunks = str_split($text, 5000);
        $processedChunks = [];

        foreach ($chunks as $chunk) {

            // Normalizar saltos de línea
            $chunk = str_replace(["\r\n", "\r"], "\n", $chunk);

            // eliminar caracteres de control invisibles
            $chunk = preg_replace('/[^\P{C}\n]+/u', '', $chunk);

            // Espacios simples (sin /u)
            $chunk = preg_replace('/[ \t]+/', ' ', $chunk);

            // Guiones
            $chunk = str_replace(['–', '—'], '-', $chunk);

            // Rangos (sin /u)
            $chunk = preg_replace('/(\d)\s*-\s*(\d)/', '$1 - $2', $chunk);

            // Normalización final
            $chunk = preg_replace('/\s+/', ' ', $chunk);

            $processedChunks[] = $chunk;
        }

        // IMPORTANTE: solo concatenar al final
        $processed = implode('', $processedChunks);

        // Debug seguro (NO duplicar texto completo)
        if (!empty($processed)) {
            file_put_contents(
                storage_path('app/debug_preprocessed.txt'),
                "===== SAMPLE =====\n\n" .
                substr($processed, 0, 500)
            );
        }

        return trim($processed);
    }
}
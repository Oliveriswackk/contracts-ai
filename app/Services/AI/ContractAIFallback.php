<?php

namespace App\Services\AI;

use App\Services\DTO\ContractDTO;

class ContractAIFallback
{
    private array $criticalFields = [
        'fecha_firma',
        'rfc_proveedor',
        'proveedor',
        'monto'
    ];


    public function enrich(ContractDTO $dto, string $text): array
    {
        $data = $dto->toArray();

        // Solo campos críticos faltantes
        $missing = array_intersect(
            $this->criticalFields,
            array_keys(
                array_filter(
                    $data,
                    fn($value) => is_null($value)
                )
            )
        );

        if (empty($missing)) {
            return [];
        }

        $aiData = $this->extractWithAI($text, $missing);

        return $this->filterValidData($aiData);
    }


    private function extractWithAI(string $text, array $fields): array
    {
        // Reducir texto ANTES de enviarlo a IA
        $text = $this->reduceTextByFields($text, $fields);

        $prompt = $this->buildPrompt($text, $fields);

        $process = proc_open(
            'ollama run mistral',
            [
                0 => ["pipe", "r"], // stdin
                1 => ["pipe", "w"], // stdout
                2 => ["pipe", "w"]  // stderr
            ],
            $pipes
        );

        if (!is_resource($process)) {
            return [];
        }

        file_put_contents(
            storage_path('app/ai_context.txt'),
            $text
        );

        $writeResult = @fwrite($pipes[0], $prompt);

        if ($writeResult === false) {
            fclose($pipes[0]);
            proc_close($process);
            return [];
        }

        fclose($pipes[0]);

        $response = stream_get_contents($pipes[1]);   

        fclose($pipes[1]);

        fclose($pipes[2]);

        proc_close($process);

        return $this->parseJson($response);
    }


    private function buildPrompt(string $text, array $fields): string
    {
        $fieldsList = implode(', ', $fields);

        return <<<PROMPT
Extrae SOLO los siguientes campos del contrato:

$fieldsList

Reglas:
- Responder SOLO en JSON válido
- No inventar datos
- Si no existe, usar null
- Si la fecha está en otro formato, conviértela a YYYY-MM-DD
- RFC en formato mexicano válido
- Monto como número sin comas

Texto:
"""
$text
"""
PROMPT;
    }


    private function reduceTextByFields(string $text, array $fields): string
    {
        $length = strlen($text);

        $chunks = [];
        $added = [];

        $addChunk = function ($key, $value) use (&$chunks, &$added) {
            if (!isset($added[$key])) {
                $chunks[] = $value;
                $added[$key] = true;
            }
        };

        // inicio
        if (
            in_array('rfc_proveedor', $fields) ||
            in_array('proveedor', $fields)
        ) {
            $addChunk('start', substr($text, 0, min(2000, $length)));
        }

        // zona media
        if (in_array('monto', $fields)) {
            $start = (int) ($length * 0.3);
            $addChunk(
                'middle',
                substr($text, $start, min(2000, $length - $start))
            );
        }

        // final
        if (in_array('fecha_firma', $fields)) {
            $addChunk(
                'end',
                substr($text, max(0, $length - 2000))
            );
        }

        // fallback mínimo
        if (empty($chunks)) {
            $chunks[] = substr($text, 0, min(2000, $length));
        }

        return implode("\n...\n", $chunks);
    }


    private function parseJson(?string $response): array
    {
        if (!$response) return [];

        $json = json_decode($response, true);

        return is_array($json) ? $json : [];
    }


    private function filterValidData(array $data): array
    {
        $valid = [];

        if (isset($data['fecha_firma']) && $this->isValidFecha($data['fecha_firma'])) {
            $valid['fecha_firma'] = $data['fecha_firma'];
        }

        if (isset($data['rfc_proveedor']) && $this->isValidRFC($data['rfc_proveedor'])) {
            $valid['rfc_proveedor'] = $data['rfc_proveedor'];
        }

        if (isset($data['proveedor']) && $this->isValidProveedor($data['proveedor'])) {
            $valid['proveedor'] = trim($data['proveedor']);
        }

        if (isset($data['monto']) && $this->isValidMonto($data['monto'])) {
            $valid['monto'] = (float) $data['monto'];
        }

        return $valid;
    }


    private function isValidRFC(?string $rfc): bool
    {
        if (!$rfc) return false;

        return preg_match('/^[A-Z&Ñ]{3,4}\d{6}[A-Z0-9]{3}$/', $rfc);
    }


    private function isValidFecha(?string $fecha): bool
    {
        if (!$fecha) return false;

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha);
    }


    private function isValidProveedor(?string $proveedor): bool
    {
        if (!$proveedor) return false;

        return mb_strlen(trim($proveedor)) > 5;
    }


    private function isValidMonto($monto): bool
    {
        return is_numeric($monto) && $monto > 0;
    }
}
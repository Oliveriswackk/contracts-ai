<?php

namespace App\Services;

class ConfidenceEvaluator
{
    private const NORMALIZER_THRESHOLD = 0.7;
    private const LOW_CONFIDENCE_THRESHOLD = 0.8;

    private array $fields = [
        'numero',
        'tipo',
        'proveedor',
        'rfc_proveedor',
        'dependencia',
        'monto',
        'fecha_firma',
        'fecha_inicio',
        'fecha_fin'
    ];

    
    public function evaluate(array $raw, array $normalized): array
    {
        $scores = [];
        $missing = [];
        $lowConfidence = [];
        $validCount = 0;

        foreach ($this->fields as $field) {
            $rawConfidence = $raw[$field]['confidence'] ?? 0.0;

            if ($normalized[$field] === null) {
                $confidence = 0.0;
                $missing[] = $field;
            } else {
                $confidence = $rawConfidence;
                $validCount++;

                if (
                    $confidence >= self::NORMALIZER_THRESHOLD &&
                    $confidence < self::LOW_CONFIDENCE_THRESHOLD
                ) {
                    $lowConfidence[] = $field;
                }
            }

            $scores[$field] = $confidence;
        }

        $globalScore = array_sum($scores) / count($this->fields);

        return [
            'global_score' => $globalScore,
            'fields' => $scores,
            'summary' => [
                'total_fields' => count($this->fields),
                'valid_fields' => $validCount,
                'missing_fields' => $missing,
                'low_confidence_fields' => $lowConfidence,
            ]
        ];
    }
}
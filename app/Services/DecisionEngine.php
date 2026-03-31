<?php

namespace App\Services;

class DecisionEngine
{
    public function decide(float $score, array $summary): array
    {
        $hasMissing = !empty($summary['missing_fields']);
        $hasLowConfidence = !empty($summary['low_confidence_fields']);

        // Regla semántica dominante
        if (!$hasMissing && !$hasLowConfidence) {
            return [
                'classification' => 'high',
                'decision' => 'accept'
            ];
        }

        // Fallback numérico
        if ($score >= 0.65) {
            return [
                'classification' => 'medium',
                'decision' => 'review'
            ];
        }

        return [
            'classification' => 'low',
            'decision' => 'intervene'
        ];
    }
}

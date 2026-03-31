<?php

namespace App\Services;

use App\Services\ContractNormalizer;
use App\Services\ConfidenceEvaluator;
use App\Services\DecisionEngine;
use App\Services\Mappers\MapperInterface;

class ContractProcessingPipeline
{
    private $normalizer;
    private $evaluator;
    private $decisionEngine;

    public function __construct(
        ContractNormalizer $normalizer,
        ConfidenceEvaluator $evaluator,
        DecisionEngine $decisionEngine
    ) {
        $this->normalizer = $normalizer;
        $this->evaluator = $evaluator;
        $this->decisionEngine = $decisionEngine;
    }

    
    public function process(MapperInterface $mapper, string $text): array
    {
        // 1. Extracción con evidencia
        $raw = $mapper->map($text);

        // 2. Normalización (filtro determinista)
        $normalized = $this->normalizer->normalize($raw);

        // 3. Evaluación de confianza
        $confidence = $this->evaluator->evaluate($raw, $normalized);

        // 4. Decisión
        $decision = $this->decisionEngine->decide(
            $confidence['global_score'],
            $confidence['summary']
        );

        return [
            'data' => $normalized,
            'confidence' => $confidence,
            'classification' => $decision['classification'],
            'decision' => $decision['decision'],
        ];
    }
}
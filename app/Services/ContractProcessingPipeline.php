<?php

namespace App\Services;

use App\Services\ContractNormalizer;
use App\Services\ConfidenceEvaluator;
use App\Services\DecisionEngine;
use App\Services\AI\ContractAIFallback;

class ContractProcessingPipeline
{
    private $normalizer;
    private $evaluator;
    private $decisionEngine;
    private ContractAIFallback $aiFallback;

    public function __construct(
        ContractNormalizer $normalizer,
        ConfidenceEvaluator $evaluator,
        DecisionEngine $decisionEngine,
        ContractAIFallback $aiFallback
    ) {
        $this->normalizer = $normalizer;
        $this->evaluator = $evaluator;
        $this->decisionEngine = $decisionEngine;
        $this->aiFallback = $aiFallback;
    }

    public function process($mapper, string $text): array
    {
        $raw = $mapper->map($text);

        // 1. Normalización inicial
        $normalized = $this->normalizer->normalize($raw);

        // 2. IA fallback (solo críticos)
        $aiData = $this->aiFallback->enrich($normalized, $text);

        if (!empty($aiData)) {
            $raw = array_merge($raw, $this->wrapAIData($aiData));
            $normalized = $this->normalizer->normalize($raw);
        }

        // 3. Evaluación
        $confidence = $this->evaluator->evaluate(
            $raw,
            $normalized->toArray()
        );

        $decision = $this->decisionEngine->decide(
            $confidence['global_score'],
            $confidence['summary']
        );

        return [
            'data' => $normalized,
            'confidence' => $confidence,
            'decision' => $decision,
        ];
    }


    private function wrapAIData(array $aiData): array
    {
        $wrapped = [];

        foreach ($aiData as $key => $value) {
            $wrapped[$key] = [
                'value' => $value,
                'confidence' => 0.6,
                'sources' => ['ai']
            ];
        }

        return $wrapped;
    }
}
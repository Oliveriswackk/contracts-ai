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

    
    public function process($mapper, string $text): array
    {
        $raw = $mapper->map($text);

        $normalized = $this->normalizer->normalize($raw); // DTO

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
}
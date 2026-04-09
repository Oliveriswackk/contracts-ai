<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PdfTextExtractor;
use App\Services\ContractNormalizer;
use App\Services\ContractProcessingPipeline;
use App\Services\ConfidenceEvaluator;
use App\Services\DecisionEngine;
use App\Services\Mappers\ContractMapper;
use App\Services\OCDSMapper;
use App\Services\AI\ContractAIFallback;
use App\Console\Animations\OCRAnimation;


class TestNormalize extends Command
{

    protected $signature = 'test:normalize {file}';
    protected $description = 'Test normalize';


    public function handle()
    {
    // Evitar mostrar error Imagick (no afecta flujo)
        error_reporting(0);
        ini_set('display_errors', 0);
        
        $file = $this->argument('file');

        $path = storage_path("app/PDFs/$file");

        $extractor = new PdfTextExtractor();

        $animation = new OCRAnimation();
        $animation->start();

        $text = $extractor->extract(
            $path,
            function ($current, $total) use ($animation) {
                $animation->update($current, $total);
            },
            function ($msg) use ($animation) {
                $animation->log($msg);
            }
        );

        $animation->finish();
        $this->output->writeln("");


        // DEBUG OCR
        file_put_contents(
            storage_path('app/debug.txt'),
            $text
        );


        $pipeline = new ContractProcessingPipeline(
            new ContractNormalizer(),
            new ConfidenceEvaluator(),
            new DecisionEngine(),
            new ContractAIFallback()
        );

        $mapper = new ContractMapper();

        $result = $pipeline->process($mapper, $text);

        $ocdsMapper = new OCDSMapper();
        $ocds = $ocdsMapper->map($result['data']);

        print_r([
            'data' => $result['data']->toArray(),
            'confidence' => $result['confidence'],
            'decision' => $result['decision'],
            'ocds' => $ocds
        ]);
    }
}
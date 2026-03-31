<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PdfTextExtractor;
use App\Services\ContractNormalizer;
use App\Services\ContractProcessingPipeline;
use App\Services\ConfidenceEvaluator;
use App\Services\DecisionEngine;
use App\Services\Mappers\ContractMapper;


class TestNormalize extends Command
{
    protected $signature = 'test:normalize {file}';

    protected $description = 'Test normalize';

    public function handle()
    {
        $file = $this->argument('file');

        $path = storage_path("app/PDFs/$file");

        $extractor = new PdfTextExtractor();

        $bar = $this->output->createProgressBar(1);
        $bar->start();

        $text = $extractor->extract(
            $path,
            function ($current, $total) use ($bar) {
                $bar->setMaxSteps($total);
                $bar->advance();
            },
            function ($msg) use ($bar) {
                $bar->clear();
                $this->output->writeln("");
                $this->output->writeln($msg);
                $bar->display();
            }
        );

        $bar->finish();
        $this->output->writeln("");

        // DEBUG OCR
        file_put_contents(
            storage_path('app/debug.txt'),
            $text
        );

        $pipeline = new ContractProcessingPipeline(
            new ContractNormalizer(),
            new ConfidenceEvaluator(),
            new DecisionEngine()
        );

        $mapper = new ContractMapper();

        $result = $pipeline->process($mapper, $text);

        print_r($result);
    }
}
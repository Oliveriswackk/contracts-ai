<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PdfTextExtractor;
use App\Services\ContractNormalizer;

class TestNormalize extends Command
{
    protected $signature = 'test:normalize {file}';

    protected $description = 'Test normalize';

    public function handle()
    {
        $file = $this->argument('file');

        $path = storage_path("app/$file");

        $extractor = new PdfTextExtractor();
        $normalizer = new ContractNormalizer();

        $bar = $this->output->createProgressBar(1);
        $bar->start();

        $text = $extractor->extract(
            $path,

            // progress callback
            function ($current, $total) use ($bar) {
                $bar->setMaxSteps($total);
                $bar->advance();
            },

            // log callback
            function ($msg) use ($bar) {

                $bar->clear();

                $this->output->writeln("");
                $this->output->writeln($msg);

                $bar->display();
            }
        );

        $bar->finish();

        $this->output->writeln("");

        $data = $normalizer->normalize($text);

        print_r($data);
    }
}
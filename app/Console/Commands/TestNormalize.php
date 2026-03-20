<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PdfTextExtractor;
use App\Services\ContractNormalizer;
use App\Services\Mappers\MapperFactory;
use App\Services\Mappers\GenericContractMapper;


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

        // Factory decide qué mapper usar
        $mapper = MapperFactory::make($text);

        // Mapper extrae
        $mapped = $mapper->map($text);

        // Normalizer limpia
        $data = $normalizer->normalize($mapped);

        print_r($data);
    }
}
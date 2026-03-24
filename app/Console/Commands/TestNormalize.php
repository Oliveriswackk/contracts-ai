<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PdfTextExtractor;
use App\Services\ContractNormalizer;
use App\Services\Mappers\MapperFactory;
use App\Services\Mappers\MapperRegistry;
use App\Services\Mappers\PrestacionServiciosMapper;
use App\Services\Mappers\AdquisicionBienesMapper;
use App\Services\Mappers\AdquisicionBienesBISMapper;
use App\Services\Mappers\ContractMapper;

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

        // DEBUG — ver texto real que detecta el OCR
        file_put_contents(
            storage_path('app/debug.txt'),
            $text
        );

        // limpiar registry
        MapperRegistry::clear();


        // REGISTRAR MAPPERS

        MapperRegistry::register(
            PrestacionServiciosMapper::class,
            fn($text) =>
                str_contains($text, 'SESEA/AD/')
                || str_contains($text, 'Grupo Mae')
                || str_contains($text, 'PRESTACIÓN DE SERVICIOS')
        );

        MapperRegistry::register(
            AdquisicionBienesBISMapper::class,
            fn($text) =>
                preg_match('/SESEA\/LPE\/\d+\/\d+BIS/', $text)
        );

        MapperRegistry::register(
            AdquisicionBienesMapper::class,
            fn($text) =>
                preg_match('/SESEA\/LPE\/\d+\/\d+/', $text)
                && !preg_match('/BIS/', $text)
        );

        MapperRegistry::register(
            ContractMapper::class,
            fn($text) =>
                str_contains($text, 'SESEA/')
        );


        // Factory decide qué mapper usar
        $mapper = MapperFactory::make($text);


        // Mapper extrae
        $mapped = $mapper->map($text);


        // Normalizer limpia
        $data = $normalizer->normalize($mapped);


        print_r($data);
    }
}
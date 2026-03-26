<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PdfTextExtractor;

class TestExtractor extends Command
{
    protected $signature = 'test:extract {file}';

    protected $description = 'Test extractor inteligente';

    public function handle()
    {
        $file = $this->argument('file');

        $path = storage_path("app/PDFs/$file");

        $extractor = new PdfTextExtractor();

        $text = $extractor->extract($path);

        echo "\n---- TEXTO ----\n";
        echo $text;
    }
}
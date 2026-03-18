<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Imagick;

class TestOCR extends Command
{
    protected $signature = 'test:ocr';

    protected $description = 'Prueba OCR con Imagick + Tesseract';

    public function handle()
    {
        $pdfPath = storage_path('app/sample_scanned.pdf');
        $outputDir = storage_path('app/ocr_output/');

        if (!file_exists($pdfPath)) {
            $this->error("No existe sample_scanned.pdf en storage/app");
            return;
        }

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $this->info("Leyendo PDF...");

        $imagick = new Imagick();
        $imagick->setResolution(300, 300);
        $imagick->readImage($pdfPath);

        $text = '';

        foreach ($imagick as $i => $page) {

            $page->setImageFormat('png');

            $imagePath = $outputDir . "page_$i.png";

            $page->writeImage($imagePath);

            $this->info("OCR página $i...");

            $ocr = new TesseractOCR($imagePath);
            $ocr->lang('spa');

            $pageText = $ocr->run();

            $text .= $pageText . "\n\n";
        }

        $this->info("Texto extraído:");
        $this->line($text);
    }
}
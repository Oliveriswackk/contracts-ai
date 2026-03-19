<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Imagick;

class PdfTextExtractor
{
    public function extract(string $pdfPath, callable $progress = null, callable $log = null): string
    {
        $text = $this->extractWithParser($pdfPath);

        if (trim($text) !== '') {

            if ($log) {
                $log("Texto detectado con pdfparser");
            }

            return $text;
        }

        if ($log) {
            $log("No hay texto, usando OCR");
        }

        return $this->extractWithOCR($pdfPath, $progress);
    }


    private function extractWithParser(string $pdfPath): string
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);

            return $pdf->getText();
        } catch (\Exception $e) {
            return '';
        }
    }

    
    private function extractWithOCR(string $pdfPath, callable $progress = null): string
    {
        $imagick = new Imagick();
        $imagick->setResolution(300, 300);
        $imagick->readImage($pdfPath);

        $dir = storage_path("app/ocr_tmp");

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $text = '';

        $total = $imagick->getNumberImages();
        $current = 0;

        foreach ($imagick as $i => $page) {

            $imgPath = $dir . "/page_$i.png";

            $page->setImageFormat('png');
            $page->writeImage($imgPath);

            $ocr = (new TesseractOCR($imgPath))
                ->executable('C:\Program Files\Tesseract-OCR\tesseract.exe')
                ->lang('spa');

            $text .= $ocr->run() . "\n";

            $current++;

            if ($progress) {
                $progress($current, $total);
            }
        }

        return $text;
    }
}
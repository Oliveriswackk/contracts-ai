<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Imagick;

class PdfTextExtractor
{
    public function extract(string $pdfPath): string
    {
        $text = $this->extractWithParser($pdfPath);

        if (trim($text) !== '') {
            echo "Texto detectado con pdfparser\n";
            return $text;
        }

        echo "No hay texto, usando OCR\n";

        return $this->extractWithOCR($pdfPath);
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

    private function extractWithOCR(string $pdfPath): string
    {
        $imagick = new Imagick();
        $imagick->setResolution(300, 300);
        $imagick->readImage($pdfPath);

        $text = '';

        foreach ($imagick as $i => $page) {

            $imgPath = storage_path("app/ocr_page_$i.png");

            $page->setImageFormat('png');
            $page->writeImage($imgPath);

            $ocr = (new TesseractOCR($imgPath))
                ->executable('C:\Program Files\Tesseract-OCR\tesseract.exe')
                ->lang('spa');

            $text .= $ocr->run() . "\n";
        }

        return $text;
    }
}
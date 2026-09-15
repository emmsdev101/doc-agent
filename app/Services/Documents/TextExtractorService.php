<?php

namespace App\Services\Documents;

use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Text as WordText;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;

final class TextExtractorService
{
    public function extract(string $path, string $extension): string
    {
        $extension = strtolower($extension);

        $text = match ($extension) {
            'pdf' => $this->fromPdf($path),
            'docx' => $this->fromDocx($path),
            'md', 'txt' => $this->fromPlainText($path),
            default => throw new RuntimeException("Unsupported document type [{$extension}]."),
        };

        $text = trim(preg_replace("/[ \t]+/u", ' ', str_replace("\r\n", "\n", $text)) ?? $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        if ($text === '') {
            throw new RuntimeException('No extractable text was found in this document.');
        }

        return $text;
    }

    private function fromPdf(string $path): string
    {
        $pdf = (new PdfParser)->parseFile($path);

        return $pdf->getText();
    }

    private function fromPlainText(string $path): string
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('Unable to read the uploaded file.');
        }

        return $contents;
    }

    private function fromDocx(string $path): string
    {
        $phpWord = IOFactory::load($path);
        $buffer = [];

        foreach ($phpWord->getSections() as $section) {
            $this->collectText($section, $buffer);
        }

        return implode("\n", $buffer);
    }

    private function collectText(mixed $element, array &$buffer): void
    {
        if ($element instanceof WordText) {
            $buffer[] = $element->getText();

            return;
        }

        if ($element instanceof TextRun || $element instanceof AbstractContainer) {
            foreach ($element->getElements() as $child) {
                $this->collectText($child, $buffer);
            }
        }
    }
}

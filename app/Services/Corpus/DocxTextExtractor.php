<?php

namespace App\Services\Corpus;

use RuntimeException;
use ZipArchive;

/**
 * Extracts plain text from a .docx file by reading word/document.xml directly.
 * No external dependencies — .docx is just a zip with XML inside.
 */
class DocxTextExtractor
{
    public function extract(string $docxPath): string
    {
        if (! is_file($docxPath)) {
            throw new RuntimeException("File not found: {$docxPath}");
        }

        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new RuntimeException("Cannot open .docx as zip: {$docxPath}");
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException("word/document.xml missing in {$docxPath}");
        }

        // Convert paragraph/break tags to newlines, strip everything else
        $xml = preg_replace('#</w:p>#', "\n", $xml);
        $xml = preg_replace('#<w:br[^/]*/>#', "\n", $xml);
        $xml = preg_replace('#<w:tab[^/]*/>#', "\t", $xml);
        $text = strip_tags($xml);

        // Decode entities, normalise whitespace
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }
}

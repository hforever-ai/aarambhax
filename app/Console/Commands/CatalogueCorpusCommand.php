<?php

namespace App\Console\Commands;

use App\Models\DraftCatalogueEntry;
use App\Services\Corpus\CatalogueExtractor;
use Illuminate\Console\Command;
use Throwable;

class CatalogueCorpusCommand extends Command
{
    protected $signature = 'aarambhax:catalogue-corpus
        {--source=storage/app/corpus/aarambh_legal : Path containing .docx files}
        {--limit= : Max files to process this run (default: all)}
        {--force : Re-process even if already catalogued}
        {--only= : Substring filter on filename (case-insensitive)}';

    protected $description = 'Extract draft fingerprints (forum, type, statutes, inputs) from corpus .docx files and store in draft_catalogue';

    public function handle(): int
    {
        $sourceArg = $this->option('source');
        $source = $this->resolvePath($sourceArg);

        if (! is_dir($source)) {
            $this->error("Source directory not found: {$source}");
            return self::FAILURE;
        }

        $files = $this->findDocxFiles($source);
        $only = $this->option('only');
        if ($only) {
            $files = array_values(array_filter(
                $files,
                fn ($f) => str_contains(strtolower(basename($f)), strtolower($only))
            ));
        }

        $limit = $this->option('limit') ? (int) $this->option('limit') : count($files);
        $files = array_slice($files, 0, $limit);

        $this->info('Found '.count($files).' .docx files');
        $this->newLine();

        $extractor = CatalogueExtractor::make();
        $force = (bool) $this->option('force');

        $ok = 0;
        $skipped = 0;
        $errors = [];

        $bar = $this->output->createProgressBar(count($files));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%  %message%');
        $bar->setMessage('starting...');
        $bar->start();

        foreach ($files as $f) {
            $bar->setMessage(substr(basename($f), 0, 50));
            try {
                $existing = DraftCatalogueEntry::where('source_path', $f)->first();
                if ($existing && ! $force) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }
                $extractor->extractFile($f, $force);
                $ok++;
            } catch (Throwable $e) {
                $errors[] = ['file' => basename($f), 'error' => $e->getMessage()];
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Catalogued: {$ok}");
        $this->info("Skipped (already done): {$skipped}");
        if (count($errors)) {
            $this->warn('Errors: '.count($errors));
            foreach ($errors as $e) {
                $this->line('  • '.$e['file'].' — '.$e['error']);
            }
        }
        return self::SUCCESS;
    }

    private function resolvePath(string $arg): string
    {
        if (str_starts_with($arg, '/')) {
            return $arg;
        }
        return base_path($arg);
    }

    private function findDocxFiles(string $dir): array
    {
        $out = [];
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($rii as $f) {
            if ($f->isFile() && strtolower($f->getExtension()) === 'docx') {
                $out[] = $f->getPathname();
            }
        }
        sort($out);
        return $out;
    }
}

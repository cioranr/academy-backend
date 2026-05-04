<?php

namespace App\Console\Commands;

use App\Support\DiplomaFonts;
use Illuminate\Console\Command;

class FetchDiplomaFonts extends Command
{
    protected $signature   = 'diploma:fetch-fonts {--force : Re-download even if files exist}';
    protected $description = 'Download Roboto + Playfair Display TTFs into storage/app/public/fonts for diploma PDF rendering.';

    public function handle(): int
    {
        if ($this->option('force')) {
            foreach (array_keys(DiplomaFonts::SOURCES) as $name) {
                $abs = storage_path("app/public/fonts/{$name}");
                if (file_exists($abs)) @unlink($abs);
            }
        }

        $results = DiplomaFonts::ensureAll();

        foreach ($results as $name => $ok) {
            $this->line(($ok ? '<info>✓</info>' : '<error>✗</error>') . " {$name}");
        }

        return in_array(false, $results, true) ? self::FAILURE : self::SUCCESS;
    }
}

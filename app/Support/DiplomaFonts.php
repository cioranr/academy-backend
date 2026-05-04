<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DiplomaFonts
{
    /** Optional auto-fetch sources, used by the diploma:fetch-fonts artisan command. */
    public const SOURCES = [
        'Roboto-Light.ttf'           => 'https://raw.githubusercontent.com/googlefonts/roboto-2/main/src/hinted/Roboto-Light.ttf',
        'Roboto-Regular.ttf'         => 'https://raw.githubusercontent.com/googlefonts/roboto-2/main/src/hinted/Roboto-Regular.ttf',
        'Roboto-Bold.ttf'            => 'https://raw.githubusercontent.com/googlefonts/roboto-2/main/src/hinted/Roboto-Bold.ttf',
        'PlayfairDisplay-Medium.ttf' => 'https://github.com/google/fonts/raw/main/ofl/playfairdisplay/PlayfairDisplay%5Bwght%5D.ttf',
    ];

    /** Filename → font-weight CSS keyword/number. */
    private const WEIGHT_MAP = [
        'thin'       => 100,
        'extralight' => 200, 'ultralight' => 200,
        'light'      => 300,
        'regular'    => 400, 'normal' => 400, 'book' => 400,
        'medium'     => 500,
        'semibold'   => 600, 'demibold' => 600,
        'bold'       => 700,
        'extrabold'  => 800, 'ultrabold' => 800, 'heavy' => 800,
        'black'      => 900,
    ];

    /** Filename family-stem → CSS font-family name. */
    private const FAMILY_MAP = [
        'roboto'         => 'Roboto',
        'playfairdisplay'=> 'Playfair Display',
        'playfair'       => 'Playfair Display',
    ];

    /** Resolve the local filesystem path for a known font; download it if missing. */
    public static function ensure(string $name): ?string
    {
        $rel = "fonts/{$name}";
        $abs = storage_path("app/public/{$rel}");

        if (file_exists($abs) && filesize($abs) > 0) return $abs;

        $url = self::SOURCES[$name] ?? null;
        if (! $url) return null;

        try {
            $bytes = @file_get_contents($url, false, stream_context_create([
                'http' => ['timeout' => 20, 'header' => "User-Agent: monza-ares-academy\r\n"],
            ]));
            if ($bytes === false || strlen($bytes) < 1024) {
                Log::warning("DiplomaFonts: failed to fetch {$name} from {$url}");
                return null;
            }
            Storage::disk('public')->put($rel, $bytes);
            return $abs;
        } catch (\Throwable $e) {
            Log::warning("DiplomaFonts: error fetching {$name}: " . $e->getMessage());
            return null;
        }
    }

    /** Pre-download every known font; returns ['name' => bool] map. */
    public static function ensureAll(): array
    {
        $out = [];
        foreach (array_keys(self::SOURCES) as $name) {
            $out[$name] = self::ensure($name) !== null;
        }
        return $out;
    }

    /**
     * Scan storage/app/public/fonts for TTF files and infer their CSS face descriptors.
     * Returns a list of ['family', 'weight', 'style', 'path'] suitable for @font-face.
     */
    public static function scanFaces(): array
    {
        $dir = storage_path('app/public/fonts');
        if (! is_dir($dir)) return [];

        $faces = [];
        foreach (glob($dir . '/*.{ttf,TTF}', GLOB_BRACE) ?: [] as $path) {
            $base = pathinfo($path, PATHINFO_FILENAME);
            $face = self::parseFaceFromFilename($base);
            if (! $face) continue;
            $face['path'] = $path;
            $faces[] = $face;
        }
        return $faces;
    }

    /** Parse "Roboto-Bold", "PlayfairDisplay-MediumItalic", etc. */
    private static function parseFaceFromFilename(string $base): ?array
    {
        $parts  = preg_split('/[-_\s]+/', $base);
        $family = null;
        $variant = '';

        foreach ($parts as $p) {
            $key = strtolower(preg_replace('/\W+/', '', $p));
            if ($key === '') continue;
            if ($family === null && isset(self::FAMILY_MAP[$key])) {
                $family = self::FAMILY_MAP[$key];
            } else {
                $variant .= $key;
            }
        }
        if (! $family) return null;

        $style  = str_contains($variant, 'italic') || str_contains($variant, 'oblique')
            ? 'italic' : 'normal';
        $weight = 400;
        foreach (self::WEIGHT_MAP as $kw => $w) {
            if (str_contains($variant, $kw)) { $weight = $w; break; }
        }
        return ['family' => $family, 'weight' => $weight, 'style' => $style];
    }
}

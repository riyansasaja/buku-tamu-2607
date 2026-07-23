<?php

namespace App\Support;

use RuntimeException;

final class BuiltAsset
{
    /** @var array<string, array<string, mixed>>|null */
    private static ?array $manifest = null;

    public static function url(string $source): string
    {
        $entry = self::manifest()[$source] ?? null;
        $file = is_array($entry) ? ($entry['file'] ?? null) : null;

        if (! is_string($file)) {
            throw new RuntimeException("Asset {$source} tidak ditemukan pada manifest Vite.");
        }

        return asset('build/'.$file);
    }

    /** @return array<string, array<string, mixed>> */
    private static function manifest(): array
    {
        if (self::$manifest !== null) {
            return self::$manifest;
        }

        $path = public_path('build/manifest.json');
        if (! is_file($path)) {
            throw new RuntimeException('Manifest Vite belum tersedia. Jalankan npm run build.');
        }

        $manifest = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($manifest)) {
            throw new RuntimeException('Manifest Vite tidak valid.');
        }

        return self::$manifest = $manifest;
    }
}

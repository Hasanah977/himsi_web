<?php

namespace App\Support;

use App\Models\PublicContent;
use Illuminate\Support\Facades\Storage;

class Appearance
{
    /** @param array<string, mixed> $settings */
    public static function remember(array $settings): void
    {
        $backgrounds = array_intersect_key($settings, array_flip(['sidebar_background', 'maintenance_background', 'auth_background']));
        if (! Storage::disk('local')->put('appearance.json', json_encode($backgrounds, JSON_THROW_ON_ERROR))) {
            throw new \RuntimeException('Pengaturan latar tidak dapat disimpan.');
        }
    }

    public static function background(string $key): string
    {
        $stored = Storage::disk('local')->get('appearance.json');
        $settings = json_decode($stored ?? '{}', true);
        $filename = is_array($settings) ? ($settings[$key] ?? null) : null;
        if (is_string($filename) && $filename !== '' && $filename !== 'placeholder.svg') {
            return PublicContent::imageUrl($filename);
        }

        return asset('images/himsi/sidebar-background.webp');
    }
}

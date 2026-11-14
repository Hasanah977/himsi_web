<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PublicContent extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public static function items(string $section): array
    {
        return static::query()
            ->where('section', $section)
            ->where('is_published', true)
            ->whereNull('department')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(function ($item) {
                return array_merge($item->payload, [
                    'slug' => $item->slug,
                ]);
            })
            ->all();
    }

    public static function settings(): array
    {
        $record = static::query()
            ->where('section', 'settings')
            ->where('slug', 'site')
            ->first();

        return array_replace(
            config('superadmin.defaults', []),
            $record?->payload ?? []
        );
    }

    public static function heroSlides(): array
    {
        $slides = static::items('hero_slides');
        if ($slides !== []) {
            return $slides;
        }

        return static::where('section', 'gallery')->where('is_published', true)->whereNull('department')
            ->latest('id')->limit(5)->get()->pluck('payload')->all();
    }

    public static function announcementsFor(string $department): Builder
    {
        return static::query()->where('section', 'announcements')->where('is_published', true)
            ->where(fn (Builder $query) => $query->whereNull('department')->orWhere('department', $department));
    }

    public static function imageUrl(?string $filename): string
    {
        $filename = (string) $filename;

        // File yang diunggah melalui Super Admin.
        if (preg_match(
            '~^storage/himsi/[a-zA-Z0-9._-]+$~',
            $filename
        )) {
            return asset($filename);
        }

        // Gambar lama di public/images/himsi.
        if (
            $filename !== ''
            && basename($filename) === $filename
            && file_exists(public_path('images/himsi/'.$filename))
        ) {
            return asset('images/himsi/'.$filename);
        }

        return asset('images/himsi/placeholder.svg');
    }
}

<?php

namespace Database\Seeders;

use App\Models\PublicContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PublicContentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pengaturan Utama Situs (Settings)
        PublicContent::updateOrCreate(
            ['section' => 'settings', 'slug' => 'site'],
            [
                'payload' => config('superadmin.defaults', []),
                'position' => 1,
                'is_published' => true,
            ]
        );

        // 2. Sections dari config('himsi')
        $sections = [
            'quotes' => config('himsi.quotes', []),
            'hero_slides' => config('himsi.hero_slides', []),
            'achievements' => config('himsi.achievements', []),
            'programs' => config('himsi.programs', []),
            'announcements' => config('himsi.announcements', []),
            'gallery' => config('himsi.gallery', []),
            'leaders' => config('himsi.leaders', []),
            'departments' => config('himsi.departments', []),
        ];

        foreach ($sections as $section => $items) {
            foreach ($items as $index => $item) {
                $slug = $item['slug'] ?? Str::slug($item['title'] ?? $item['name'] ?? "{$section}-".($index + 1));
                PublicContent::updateOrCreate(
                    [
                        'section' => $section,
                        'slug' => $slug,
                    ],
                    [
                        'payload' => $item,
                        'position' => $index + 1,
                        'is_published' => true,
                    ]
                );
            }
        }
    }
}

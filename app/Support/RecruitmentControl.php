<?php

namespace App\Support;

use App\Models\PublicContent;

class RecruitmentControl
{
    public static function enabled(): bool
    {
        $settings = PublicContent::where('section', 'settings')->where('slug', 'governance')->first();

        return (bool) ($settings?->payload['recruitment_enabled'] ?? true);
    }
}

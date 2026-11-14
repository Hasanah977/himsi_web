<?php

return [
    'directory' => storage_path('app/private/security'),
    'backup_disk' => 'local',
    'backup_limit_bytes' => 128 * 1024 * 1024,
    'bulk_delete_threshold' => 10,
    'public_routes' => ['home', 'profile', 'structure', 'programs', 'program.detail', 'announcements', 'announcement.detail', 'registration.create', 'registration.store', 'registration.success'],
];

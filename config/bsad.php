<?php

return [
    // Published data path (users can edit this file themselves)
    'data_path' => storage_path('app/bsad/bsad.json'),

    // Optional: where bs:update-data fetches a new dataset JSON from
    // e.g. your GitHub release raw URL
    'update_url' => env('BSAD_UPDATE_URL', null),

    // If true, keep backups when updating
    'backup_on_update' => true,

    // Locale default: 'en' or 'np'
    'locale' => 'en',

    // Use Nepali digits by default in formatter?
    'nepali_digits' => false,
];
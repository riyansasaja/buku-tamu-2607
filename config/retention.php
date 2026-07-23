<?php

return [
    'automatic_enabled' => (bool) env('RETENTION_AUTOMATIC_ENABLED', false),
    'visit_years' => (int) env('VISIT_RETENTION_YEARS', 3),
    'timezone' => env('APP_TIMEZONE', 'Asia/Makassar'),
    'batch_size' => (int) env('RETENTION_BATCH_SIZE', 100),
];

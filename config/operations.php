<?php

return [
    'initial_admin' => [
        'enabled' => env('INITIAL_ADMIN_ENABLED', false),
        'name' => env('INITIAL_ADMIN_NAME'),
        'email' => env('INITIAL_ADMIN_EMAIL'),
        'whatsapp' => env('INITIAL_ADMIN_WHATSAPP'),
        'password' => env('INITIAL_ADMIN_PASSWORD'),
    ],
    'scheduler_heartbeat_key' => 'operations:scheduler:last_seen',
    'scheduler_stale_after_minutes' => (int) env('SCHEDULER_STALE_AFTER_MINUTES', 5),
    'queue_backlog_warning' => (int) env('QUEUE_BACKLOG_WARNING', 100),
    'failed_jobs_warning' => (int) env('FAILED_JOBS_WARNING', 1),
];

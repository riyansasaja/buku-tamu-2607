<?php

return [
    'client_key' => env('API_CLIENT_KEY'),
    'photo_url_minutes' => 10,
    'rate_limits' => [
        'employees' => 60,
        'visits' => 20,
        'decision_pages' => 30,
        'decision_actions' => 10,
    ],
];

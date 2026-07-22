<?php

namespace App\Support;

final class HealthStatus
{
    /**
     * @return array{status: 'ok', checks: array{application: 'ok', database: 'ok'}}
     */
    public static function healthy(): array
    {
        return [
            'status' => 'ok',
            'checks' => [
                'application' => 'ok',
                'database' => 'ok',
            ],
        ];
    }

    /**
     * @return array{status: 'degraded', checks: array{application: 'ok', database: 'unavailable'}}
     */
    public static function databaseUnavailable(): array
    {
        return [
            'status' => 'degraded',
            'checks' => [
                'application' => 'ok',
                'database' => 'unavailable',
            ],
        ];
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_check_reports_application_and_database_as_healthy(): void
    {
        DB::shouldReceive('select')
            ->once()
            ->with('select 1')
            ->andReturn([(object) ['connected' => 1]]);

        $this->getJson('/up')
            ->assertOk()
            ->assertExactJson([
                'status' => 'ok',
                'checks' => [
                    'application' => 'ok',
                    'database' => 'ok',
                ],
            ]);
    }

    public function test_health_check_reports_a_database_failure_without_leaking_details(): void
    {
        DB::shouldReceive('select')
            ->once()
            ->with('select 1')
            ->andThrow(new RuntimeException('mysql://secret-user:secret-password@internal-host'));

        $response = $this->getJson('/up');

        $response
            ->assertServiceUnavailable()
            ->assertExactJson([
                'status' => 'degraded',
                'checks' => [
                    'application' => 'ok',
                    'database' => 'unavailable',
                ],
            ]);

        $this->assertStringNotContainsString('secret', $response->getContent());
        $this->assertStringNotContainsString('internal-host', $response->getContent());
    }
}

<?php

namespace Tests\Unit;

use App\Support\HealthStatus;
use PHPUnit\Framework\TestCase;

class HealthStatusTest extends TestCase
{
    public function test_database_unavailable_status_keeps_the_application_healthy(): void
    {
        $status = HealthStatus::databaseUnavailable();

        $this->assertSame('degraded', $status['status']);
        $this->assertSame('ok', $status['checks']['application']);
        $this->assertSame('unavailable', $status['checks']['database']);
    }
}

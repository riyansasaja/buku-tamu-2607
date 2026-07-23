<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\ProductionAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class ProductionOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_web_request_receives_a_request_id(): void
    {
        $this->get('/')->assertOk()->assertHeader('X-Request-ID');
        $this->get('/', ['X-Request-ID' => 'office-test-123'])
            ->assertOk()
            ->assertHeader('X-Request-ID', 'office-test-123');
    }

    public function test_production_admin_seeder_creates_once_without_resetting_password(): void
    {
        app()->detectEnvironment(fn (): string => 'production');
        config()->set('operations.initial_admin', [
            'enabled' => true,
            'name' => 'Administrator PTA Manado',
            'email' => 'ADMIN@PTAMANADO.COM',
            'whatsapp' => '6281234567890',
            'password' => 'Kuat-Sekali-123!',
        ]);

        $this->assertSame(0, Artisan::call('db:seed', ['--class' => ProductionAdminSeeder::class, '--force' => true]));
        $admin = User::query()->sole();

        $this->assertSame('admin@ptamanado.com', $admin->email);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check('Kuat-Sekali-123!', $admin->password));

        config()->set('operations.initial_admin.password', 'Berbeda-Sekali-456!');
        $this->assertSame(0, Artisan::call('db:seed', ['--class' => ProductionAdminSeeder::class, '--force' => true]));

        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(Hash::check('Kuat-Sekali-123!', $admin->fresh()->password));
    }

    public function test_production_admin_seeder_refuses_non_production_and_disabled_bootstrap(): void
    {
        $this->expectException(RuntimeException::class);
        $this->seed(ProductionAdminSeeder::class);
    }

    public function test_operations_heartbeat_and_status_detect_healthy_queue(): void
    {
        config()->set('operations.failed_jobs_warning', 1);
        config()->set('operations.queue_backlog_warning', 100);

        $this->assertSame(0, Artisan::call('operations:heartbeat'));
        $this->assertNotNull(Cache::get(config('operations.scheduler_heartbeat_key')));
        $this->assertSame(0, Artisan::call('operations:status', ['--json' => true]));
        $this->assertStringContainsString('"status":"ok"', Artisan::output());
    }
}

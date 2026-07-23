<?php

namespace Tests\Feature;

use App\Logging\RedactSensitiveContext;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Visit;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Log\Logger as IlluminateLogger;
use Illuminate\Support\Facades\Storage;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_applied_to_web_responses(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Permissions-Policy')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_logging_processor_redacts_nested_sensitive_context(): void
    {
        $handler = new TestHandler;
        $logger = new Logger('security-test', [$handler]);
        (new RedactSensitiveContext)(new IlluminateLogger($logger));
        $logger->info('safe message', ['password' => 'secret', 'nested' => ['guest_whatsapp' => '628111111111', 'safe' => 'ok']]);
        $context = $handler->getRecords()[0]->context;

        $this->assertSame('[REDACTED]', $context['password']);
        $this->assertSame('[REDACTED]', $context['nested']['guest_whatsapp']);
        $this->assertSame('ok', $context['nested']['safe']);
    }

    public function test_script_disguised_as_photo_is_rejected(): void
    {
        $employee = Employee::factory()->create();
        $photo = UploadedFile::fake()->createWithContent('visitor.jpg', "\xFF\xD8\xFF<?php echo 'attack'; ?>");

        $this->withHeaders(['X-Client-Key' => config('api.client_key'), 'Idempotency-Key' => 'security-upload-1'])
            ->postJson(route('api.v1.visits.store'), [
                'guest_name' => 'Penguji Keamanan', 'address' => 'Manado', 'employee_id' => $employee->id,
                'guest_whatsapp' => '081234567890', 'visit_purpose' => 'Pengujian upload', 'photo' => $photo,
            ])->assertUnprocessable()->assertJsonValidationErrors('photo');
    }

    public function test_retention_dry_run_and_calendar_cutoff_are_safe(): void
    {
        Storage::fake('local');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2029-01-02 02:30', 'Asia/Makassar'));
        $employee = Employee::factory()->create();
        $expired = Visit::factory()->for($employee)->create(['arrived_at' => CarbonImmutable::parse('2026-12-31 23:59', 'Asia/Makassar'), 'photo_path' => 'visits/expired.jpg']);
        $retained = Visit::factory()->for($employee)->create(['arrived_at' => CarbonImmutable::parse('2027-01-01 00:00', 'Asia/Makassar'), 'photo_path' => 'visits/retained.jpg']);
        Storage::disk('local')->put($expired->photo_path, 'expired');
        Storage::disk('local')->put($retained->photo_path, 'retained');
        AuditLog::query()->create(['actor_type' => 'test', 'action' => 'visit.test', 'auditable_type' => Visit::class, 'auditable_id' => $expired->id, 'metadata' => [], 'request_id' => 'test-run']);

        $this->artisan('visits:purge-expired --dry-run')->assertSuccessful()->expectsOutputToContain('1 kunjungan');
        $this->assertModelExists($expired);
        $this->artisan('visits:purge-expired --batch=1')->assertSuccessful();

        $this->assertModelMissing($expired);
        $this->assertModelExists($retained);
        Storage::disk('local')->assertMissing('visits/expired.jpg');
        Storage::disk('local')->assertExists('visits/retained.jpg');
        $this->assertDatabaseMissing('audit_logs', ['auditable_type' => Visit::class, 'auditable_id' => $expired->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'retention.visits_purged']);
        CarbonImmutable::setTestNow();
    }
}

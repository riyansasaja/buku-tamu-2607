<?php

namespace Tests\Feature\Api\V1;

use App\Enums\NotificationType;
use App\Jobs\SendVisitWhatsApp;
use App\Models\Employee;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class VisitApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['api.client_key' => 'test-client-key']);
        Storage::fake('local');
        Queue::fake();
    }

    public function test_api_requires_valid_client_key_and_returns_request_id(): void
    {
        $response = $this->getJson('/api/v1/employees', ['X-Request-ID' => 'client-request-123']);

        $response->assertUnauthorized()
            ->assertHeader('X-Request-ID', 'client-request-123')
            ->assertJsonPath('request_id', 'client-request-123');
    }

    public function test_employee_list_is_active_alphabetical_searchable_and_paginated(): void
    {
        Employee::factory()->create(['name' => 'Zul Kurnia', 'notification_contact' => '6281234567890']);
        $andi = Employee::factory()->create(['name' => 'Andi Manado']);
        Employee::factory()->inactive()->create(['name' => 'Abdi Nonaktif']);
        Employee::factory()->create(['name' => 'Aktif Tanpa WhatsApp', 'notification_contact' => null]);

        $response = $this->getJson('/api/v1/employees?q=andi&per_page=1', $this->clientHeaders());

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $andi->id)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonMissingPath('data.0.notification_contact');

        $this->getJson('/api/v1/employees?q=Aktif%20Tanpa', $this->clientHeaders())
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_visit_can_be_created_with_private_photo_and_signed_url(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->post('/api/v1/visits', $this->payload($employee), $this->clientHeaders('visit-001'));

        $response->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'false')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.employee.id', $employee->id)
            ->assertJsonStructure(['data' => ['id', 'visit_number', 'photo_url'], 'meta' => ['request_id']]);

        $visit = Visit::query()->sole();
        $this->assertStringStartsWith('BTM-', $visit->visit_number);
        $this->assertStringNotContainsString('guest.jpg', $visit->photo_path);
        Storage::disk('local')->assertExists($visit->photo_path);
        $this->assertDatabaseMissing('visits', ['idempotency_key_hash' => 'visit-001']);
        $this->assertSame('6281234567890', $visit->guest_whatsapp);
        $this->assertNotSame('6281234567890', DB::table('visits')->where('id', $visit->id)->value('guest_whatsapp'));
        $response->assertJsonMissingPath('data.guest_whatsapp');

        $photoResponse = $this->get($response->json('data.photo_url'))->assertOk();
        $this->assertStringContainsString('no-store', $photoResponse->headers->get('Cache-Control'));
        $this->get("/api/v1/visits/{$visit->id}/photo")->assertForbidden();
    }

    public function test_validation_rejects_inactive_employee_and_unsafe_photo_without_partial_data(): void
    {
        $employee = Employee::factory()->inactive()->create();
        $photo = UploadedFile::fake()->createWithContent('malware.jpg', '<?php echo "unsafe";');

        $response = $this->post('/api/v1/visits', $this->payload($employee, $photo), $this->clientHeaders('invalid-001'));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['employee_id', 'photo']);
        $this->assertDatabaseCount('visits', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_photo_larger_than_five_megabytes_is_rejected(): void
    {
        $employee = Employee::factory()->create();
        $photo = UploadedFile::fake()->image('large.jpg')->size(5121);

        $this->post('/api/v1/visits', $this->payload($employee, $photo), $this->clientHeaders('large-001'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');
        $this->assertDatabaseCount('visits', 0);
    }

    public function test_visit_rejects_invalid_guest_whatsapp_and_employee_without_whatsapp(): void
    {
        $employee = Employee::factory()->create(['notification_contact' => null]);
        $payload = $this->payload($employee);
        $payload['guest_whatsapp'] = 'nomor-salah';

        $this->post('/api/v1/visits', $payload, $this->clientHeaders('whatsapp-invalid'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['guest_whatsapp', 'employee_id']);
        $this->assertDatabaseCount('visits', 0);
    }

    public function test_required_fields_are_reported_with_standard_error_shape(): void
    {
        $response = $this->post('/api/v1/visits', [], $this->clientHeaders('empty-001'));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['guest_name', 'address', 'guest_whatsapp', 'employee_id', 'visit_purpose', 'photo'])
            ->assertJsonStructure(['message', 'errors', 'request_id'])
            ->assertHeader('X-Request-ID');
    }

    public function test_employee_endpoint_is_rate_limited(): void
    {
        config(['api.rate_limits.employees' => 2]);

        $this->getJson('/api/v1/employees', $this->clientHeaders())->assertOk();
        $this->getJson('/api/v1/employees', $this->clientHeaders())->assertOk();
        $this->getJson('/api/v1/employees', $this->clientHeaders())
            ->assertTooManyRequests()
            ->assertJsonStructure(['message', 'request_id'])
            ->assertHeader('X-Request-ID');
    }

    public function test_same_idempotency_key_and_payload_replays_without_duplicate(): void
    {
        $employee = Employee::factory()->create();
        $image = UploadedFile::fake()->image('source.jpg');
        $bytes = file_get_contents($image->getRealPath());
        $payload = fn (): array => $this->payload($employee, UploadedFile::fake()->createWithContent('guest.jpg', $bytes));

        $first = $this->post('/api/v1/visits', $payload(), $this->clientHeaders('same-key'));
        $second = $this->post('/api/v1/visits', $payload(), $this->clientHeaders('same-key'));

        $first->assertCreated();
        $second->assertOk()->assertHeader('Idempotency-Replayed', 'true');
        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseCount('visits', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles());
        Queue::assertPushed(SendVisitWhatsApp::class, 1);
        Queue::assertPushed(fn (SendVisitWhatsApp $job): bool => $job->visitId === $first->json('data.id')
            && $job->type === NotificationType::EmployeeArrival);
    }

    public function test_same_idempotency_key_with_different_payload_conflicts(): void
    {
        $employee = Employee::factory()->create();
        $image = UploadedFile::fake()->image('source.jpg');
        $bytes = file_get_contents($image->getRealPath());

        $this->post('/api/v1/visits', $this->payload($employee, UploadedFile::fake()->createWithContent('a.jpg', $bytes)), $this->clientHeaders('conflict-key'))->assertCreated();
        $payload = $this->payload($employee, UploadedFile::fake()->createWithContent('b.jpg', $bytes));
        $payload['guest_name'] = 'Nama Berbeda';

        $this->post('/api/v1/visits', $payload, $this->clientHeaders('conflict-key'))->assertConflict();
        $this->assertDatabaseCount('visits', 1);
    }

    public function test_signed_photo_url_expires_after_ten_minutes(): void
    {
        Carbon::setTestNow('2026-07-23 10:00:00');
        $employee = Employee::factory()->create();
        $response = $this->post('/api/v1/visits', $this->payload($employee), $this->clientHeaders('expiry-key'));
        $url = $response->json('data.photo_url');

        $this->get($url)->assertOk();
        Carbon::setTestNow(now()->addMinutes(11));
        $this->get($url)->assertForbidden();
        Carbon::setTestNow();
    }

    /** @return array<string, string> */
    private function clientHeaders(?string $idempotencyKey = null): array
    {
        return array_filter([
            'Accept' => 'application/json',
            'X-Client-Key' => 'test-client-key',
            'Idempotency-Key' => $idempotencyKey,
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(Employee $employee, ?UploadedFile $photo = null): array
    {
        return [
            'guest_name' => 'Tamu Pengujian',
            'address' => 'Kota Manado',
            'guest_whatsapp' => '0812-3456-7890',
            'employee_id' => $employee->id,
            'visit_purpose' => 'Konsultasi layanan pengadilan',
            'photo' => $photo ?? UploadedFile::fake()->image(Str::random(8).'.jpg'),
        ];
    }
}

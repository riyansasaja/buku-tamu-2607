<?php

namespace Tests\Feature\Auth;

use App\Contracts\WhatsAppGateway;
use App\Data\WhatsAppSendResult;
use App\Jobs\SendAdminPasswordResetWhatsApp;
use App\Models\User;
use App\Models\UserPasswordResetDelivery;
use App\Models\UserPasswordResetToken;
use App\Services\AdminPasswordResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PasswordResetWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_response_is_generic_and_only_eligible_admin_gets_job(): void
    {
        Queue::fake();
        $user = User::factory()->admin()->create(['email' => 'admin@ptamanado.com', 'whatsapp' => '628123456700', 'whatsapp_hash' => hash('sha256', '628123456700')]);

        $eligible = $this->post(route('password.email'), ['email' => 'ADMIN@PTAMANADO.COM'])->assertOk()->assertHeader('X-Request-ID')->assertSee('Permintaan diterima');
        $missing = $this->post(route('password.email'), ['email' => 'tidakada@ptamanado.com'])->assertOk()->assertSee('Permintaan diterima');
        $this->assertSame($eligible->getContent(), $missing->getContent());
        Queue::assertPushed(SendAdminPasswordResetWhatsApp::class, 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.password_reset_scheduled', 'auditable_id' => $user->id]);
    }

    public function test_reset_job_hides_plain_token_and_records_sanitized_delivery(): void
    {
        Queue::fake();
        $user = User::factory()->admin()->create(['whatsapp' => '628123456701', 'whatsapp_hash' => hash('sha256', '628123456701')]);
        app(AdminPasswordResetService::class)->issue($user);
        $job = null;
        Queue::assertPushed(SendAdminPasswordResetWhatsApp::class, function (SendAdminPasswordResetWhatsApp $queued) use (&$job): bool {
            $job = $queued;

            return true;
        });
        $this->assertInstanceOf(SendAdminPasswordResetWhatsApp::class, $job);
        $plain = Crypt::decryptString($job->encryptedToken);
        $this->assertStringNotContainsString($plain, serialize($job));
        $gateway = new class implements WhatsAppGateway
        {
            public string $message = '';

            public function send(string $target, string $message): WhatsAppSendResult
            {
                $this->message = $message;

                return new WhatsAppSendResult('m1', 'r1');
            }
        };
        $job->handle($gateway);
        $this->assertStringContainsString(route('password.reset', $plain), $gateway->message);
        $delivery = UserPasswordResetDelivery::query()->sole();
        $this->assertSame('sent', $delivery->status->value);
        $this->assertStringNotContainsString($plain, json_encode($delivery->toArray(), JSON_THROW_ON_ERROR));
    }

    public function test_password_is_changed_atomically_and_token_cannot_be_replayed(): void
    {
        Queue::fake();
        $user = User::factory()->admin()->create(['password' => Hash::make('PasswordLama123'), 'whatsapp' => '628123456702', 'whatsapp_hash' => hash('sha256', '628123456702')]);
        app(AdminPasswordResetService::class)->issue($user);
        $job = null;
        Queue::assertPushed(SendAdminPasswordResetWhatsApp::class, function (SendAdminPasswordResetWhatsApp $queued) use (&$job): bool {
            $job = $queued;

            return true;
        });
        $this->assertInstanceOf(SendAdminPasswordResetWhatsApp::class, $job);
        $plain = Crypt::decryptString($job->encryptedToken);

        $this->get(route('password.reset', $plain))->assertOk()->assertHeader('Cache-Control', 'max-age=0, no-store, private')->assertHeader('X-Request-ID');
        $this->post(route('password.update', $plain), ['password' => 'PasswordBaru123', 'password_confirmation' => 'PasswordBaru123'])->assertOk()->assertSee('Password berhasil diperbarui');
        $this->assertTrue(Hash::check('PasswordBaru123', $user->fresh()->password));
        $this->assertFalse(Hash::check('PasswordLama123', $user->fresh()->password));
        $this->get(route('password.reset', $plain))->assertNotFound()->assertSee('Tautan tidak tersedia');
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.password_reset_completed', 'auditable_id' => $user->id]);
    }

    public function test_new_request_revokes_old_reset_without_touching_activation_token(): void
    {
        Queue::fake();
        $user = User::factory()->admin()->create(['whatsapp' => '628123456703', 'whatsapp_hash' => hash('sha256', '628123456703')]);
        $service = app(AdminPasswordResetService::class);
        $service->issue($user);
        $old = UserPasswordResetToken::query()->sole();
        $service->issue($user);
        $this->assertNotNull($old->fresh()->revoked_at);
        $this->assertDatabaseCount('user_password_reset_tokens', 2);
        $this->assertDatabaseCount('user_activation_tokens', 0);
    }

    public function test_reset_request_is_rate_limited(): void
    {
        Queue::fake();
        foreach (range(1, 3) as $attempt) {
            $this->post(route('password.email'), ['email' => 'same@ptamanado.com'])->assertOk();
        }
        $this->post(route('password.email'), ['email' => 'same@ptamanado.com'])->assertTooManyRequests();
    }
}

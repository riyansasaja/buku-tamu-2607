<?php

namespace Tests\Feature\Admin;

use App\Contracts\WhatsAppGateway;
use App\Data\WhatsAppSendResult;
use App\Enums\NotificationDeliveryStatus;
use App\Jobs\SendAdminActivationWhatsApp;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserActivationToken;
use App\Models\UserNotificationDelivery;
use App\Services\AdminActivationService;
use App\Support\WhatsAppNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_account_without_password_and_fonnte_job_uses_encrypted_token(): void
    {
        Queue::fake();
        $actor = User::factory()->admin()->create();

        $this->actingAs($actor)->post(route('admin.users.store'), [
            'name' => 'Admin Baru', 'email' => 'ADMIN.BARU@PTAMANADO.COM', 'whatsapp' => '0812-3456-7890', 'is_active' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'admin.baru@ptamanado.com')->sole();
        $this->assertSame('6281234567890', $user->whatsapp);
        $this->assertSame(hash('sha256', '6281234567890'), $user->whatsapp_hash);
        $this->assertNull($user->activated_at);
        $this->assertFalse(Hash::check('admin12345', $user->password));
        $token = UserActivationToken::query()->where('user_id', $user->id)->sole();
        $this->assertSame(64, strlen($token->token_hash));

        $job = null;
        Queue::assertPushed(SendAdminActivationWhatsApp::class, function (SendAdminActivationWhatsApp $queued) use (&$job): bool {
            $job = $queued;

            return true;
        });
        $this->assertInstanceOf(SendAdminActivationWhatsApp::class, $job);
        $plain = Crypt::decryptString($job->encryptedToken);
        $this->assertSame($token->token_hash, hash('sha256', $plain));
        $this->assertStringNotContainsString($plain, serialize($job));

        $gateway = new class implements WhatsAppGateway
        {
            public string $target = '';

            public string $message = '';

            public function send(string $target, string $message): WhatsAppSendResult
            {
                $this->target = $target;
                $this->message = $message;

                return new WhatsAppSendResult('message-1', 'request-1');
            }
        };
        $job->handle($gateway);
        $this->assertSame('6281234567890', $gateway->target);
        $this->assertStringContainsString(route('activation.show', $plain), $gateway->message);
        $this->assertSame(NotificationDeliveryStatus::Sent, UserNotificationDelivery::query()->sole()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.created', 'auditable_id' => $user->id]);
        $this->assertStringNotContainsString($plain, json_encode(AuditLog::query()->sole()->metadata, JSON_THROW_ON_ERROR));
    }

    public function test_activation_sets_password_once_and_invalid_links_are_indistinguishable(): void
    {
        Queue::fake();
        $actor = User::factory()->admin()->create();
        $this->actingAs($actor)->post(route('admin.users.store'), [
            'name' => 'Admin Aktivasi', 'email' => 'aktivasi@ptamanado.com', 'whatsapp' => '081234567891', 'is_active' => '1',
        ]);
        $job = null;
        Queue::assertPushed(SendAdminActivationWhatsApp::class, function (SendAdminActivationWhatsApp $queued) use (&$job): bool {
            $job = $queued;

            return true;
        });
        $this->assertInstanceOf(SendAdminActivationWhatsApp::class, $job);
        $plain = Crypt::decryptString($job->encryptedToken);

        $this->get(route('activation.show', $plain))->assertOk()->assertSee('Tentukan Password');
        $this->post(route('activation.store', $plain), ['password' => 'PasswordBaru123', 'password_confirmation' => 'PasswordBaru123'])->assertOk()->assertSee('Akun berhasil diaktifkan');

        $user = User::query()->where('email', 'aktivasi@ptamanado.com')->sole();
        $this->assertNotNull($user->activated_at);
        $this->assertTrue(Hash::check('PasswordBaru123', $user->password));
        $this->get(route('activation.show', $plain))->assertNotFound()->assertSee('Tautan tidak tersedia');
        $this->get(route('activation.show', str_repeat('x', 64)))->assertNotFound()->assertSee('Tautan tidak tersedia');
        $this->post(route('activation.store', $plain), ['password' => 'PasswordLain123', 'password_confirmation' => 'PasswordLain123'])->assertNotFound();
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.activated', 'auditable_id' => $user->id]);
    }

    public function test_resend_revokes_previous_token_and_creates_new_delivery_job(): void
    {
        Queue::fake();
        $actor = User::factory()->admin()->create();
        $target = User::factory()->admin()->create(['activated_at' => null, 'whatsapp' => '628123456792', 'whatsapp_hash' => hash('sha256', '628123456792')]);
        app(AdminActivationService::class)->issue($target);
        $old = UserActivationToken::query()->where('user_id', $target->id)->sole();

        $this->actingAs($actor)->post(route('admin.users.resend-activation', $target))->assertRedirect();

        $this->assertNotNull($old->fresh()->revoked_at);
        $this->assertDatabaseCount('user_activation_tokens', 2);
        Queue::assertPushed(SendAdminActivationWhatsApp::class, 2);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.activation_resent', 'auditable_id' => $target->id]);
    }

    public function test_admin_cannot_disable_self_or_last_active_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->patch(route('admin.users.status', $admin), ['is_active' => 0])->assertSessionHasErrors('is_active');
        $this->assertTrue($admin->fresh()->is_active);

        $inactiveTarget = User::factory()->admin()->inactive()->create();
        $this->patch(route('admin.users.status', $inactiveTarget), ['is_active' => 1])->assertSessionHasNoErrors();
        $this->patch(route('admin.users.status', $inactiveTarget), ['is_active' => 0])->assertSessionHasNoErrors();
        $this->assertFalse($inactiveTarget->fresh()->is_active);
    }

    public function test_guest_and_non_admin_cannot_manage_admin_accounts(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_email_and_normalized_whatsapp_must_be_unique(): void
    {
        Queue::fake();
        $actor = User::factory()->admin()->create();
        User::factory()->admin()->create([
            'email' => 'existing@ptamanado.com',
            'whatsapp' => '6281234500000',
            'whatsapp_hash' => hash('sha256', '6281234500000'),
        ]);
        $this->assertDatabaseHas('users', ['whatsapp_hash' => hash('sha256', '6281234500000')]);
        $this->assertSame('6281234500000', WhatsAppNumber::normalize('0812-3450-0000'));

        $this->actingAs($actor)->post(route('admin.users.store'), [
            'name' => 'Duplikat',
            'email' => 'unique@ptamanado.com',
            'whatsapp' => '0812-3450-0000',
            'is_active' => 1,
        ])->assertSessionHasErrors('whatsapp_hash');

        $this->post(route('admin.users.store'), [
            'name' => 'Duplikat',
            'email' => 'EXISTING@PTAMANADO.COM',
            'whatsapp' => '08123450001',
            'is_active' => 1,
        ])->assertSessionHasErrors('email');
    }

    public function test_unactivated_admin_cannot_login_even_with_matching_password(): void
    {
        User::factory()->admin()->create([
            'email' => 'belum.aktif@ptamanado.com',
            'password' => Hash::make('PasswordCocok123'),
            'activated_at' => null,
        ]);

        $this->post(route('login.store'), [
            'email' => 'belum.aktif@ptamanado.com',
            'password' => 'PasswordCocok123',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_edit_identity_and_new_whatsapp_receives_fresh_invitation(): void
    {
        Queue::fake();
        $actor = User::factory()->admin()->create();
        $target = User::factory()->admin()->create([
            'activated_at' => null,
            'whatsapp' => '628123456700',
            'whatsapp_hash' => hash('sha256', '628123456700'),
        ]);
        app(AdminActivationService::class)->issue($target);
        $oldToken = UserActivationToken::query()->where('user_id', $target->id)->sole();

        $this->actingAs($actor)->put(route('admin.users.update', $target), [
            'name' => 'Nama Diperbaiki',
            'email' => 'diperbaiki@ptamanado.com',
            'whatsapp' => '081234567701',
            'is_active' => 1,
        ])->assertRedirect(route('admin.users.index'));

        $target->refresh();
        $this->assertSame('Nama Diperbaiki', $target->name);
        $this->assertSame('diperbaiki@ptamanado.com', $target->email);
        $this->assertSame('6281234567701', $target->whatsapp);
        $this->assertNotNull($oldToken->fresh()->revoked_at);
        $this->assertDatabaseCount('user_activation_tokens', 2);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.updated', 'auditable_id' => $target->id]);
    }

    public function test_only_unactivated_non_self_admin_can_be_deleted(): void
    {
        Queue::fake();
        $actor = User::factory()->admin()->create();
        $pending = User::factory()->admin()->create(['activated_at' => null]);
        app(AdminActivationService::class)->issue($pending);

        $this->actingAs($actor)->delete(route('admin.users.destroy', $pending))->assertRedirect();
        $this->assertModelMissing($pending);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.deleted_unactivated', 'auditable_id' => $pending->id]);

        $activated = User::factory()->admin()->create();
        $this->delete(route('admin.users.destroy', $activated))->assertSessionHasErrors('delete');
        $this->assertModelExists($activated);
        $this->delete(route('admin.users.destroy', $actor))->assertSessionHasErrors('delete');
        $this->assertModelExists($actor);
    }
}

<?php

namespace App\Services;

use App\Jobs\SendAdminPasswordResetWhatsApp;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserPasswordResetToken;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminPasswordResetService
{
    public function issue(User $user): void
    {
        DB::transaction(function () use ($user): void {
            UserPasswordResetToken::query()->where('user_id', $user->id)->whereNull('used_at')->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $plain = Str::random(64);
            $token = UserPasswordResetToken::query()->create(['user_id' => $user->id, 'token_hash' => hash('sha256', $plain), 'expires_at' => now()->addMinutes(60)]);
            AuditLog::query()->create(['actor_type' => 'password_reset_request', 'action' => 'admin.password_reset_scheduled', 'auditable_type' => User::class, 'auditable_id' => $user->id, 'metadata' => [], 'request_id' => (string) str()->uuid()]);
            SendAdminPasswordResetWhatsApp::dispatch($user->id, $token->id, Crypt::encryptString($plain));
        });
    }
}

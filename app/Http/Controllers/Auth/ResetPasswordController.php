<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivationPasswordRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserPasswordResetToken;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public function show(string $token): Response
    {
        return $this->usable(UserPasswordResetToken::query()->where('token_hash', hash('sha256', $token))->first())
            ? response()->view('auth.reset-password', compact('token'))->header('Cache-Control', 'no-store, max-age=0')->header('X-Request-ID', (string) str()->uuid())
            : $this->unavailable();
    }

    public function store(StoreActivationPasswordRequest $request, string $token): Response
    {
        $user = DB::transaction(function () use ($request, $token): ?User {
            $record = UserPasswordResetToken::query()->where('token_hash', hash('sha256', $token))->lockForUpdate()->first();
            if (! $this->usable($record)) {
                return null;
            }
            $user = User::query()->whereKey($record->user_id)->where('is_active', true)->whereNotNull('activated_at')->lockForUpdate()->first();
            if ($user === null) {
                return null;
            }
            $user->update(['password' => Hash::make((string) $request->validated('password')), 'remember_token' => Str::random(60)]);
            $record->update(['used_at' => now()]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
            AuditLog::query()->create(['actor_type' => 'password_reset_link', 'action' => 'admin.password_reset_completed', 'auditable_type' => User::class, 'auditable_id' => $user->id, 'metadata' => [], 'request_id' => (string) str()->uuid()]);

            return $user;
        }, 3);

        return $user
            ? response()->view('auth.reset-password-complete')->header('Cache-Control', 'no-store, max-age=0')->header('X-Request-ID', (string) str()->uuid())
            : $this->unavailable();
    }

    private function usable(?UserPasswordResetToken $token): bool
    {
        return $token !== null && $token->used_at === null && $token->revoked_at === null && $token->expires_at->isFuture();
    }

    private function unavailable(): Response
    {
        return response()->view('auth.reset-password-unavailable', [], 404)->header('Cache-Control', 'no-store, max-age=0')->header('X-Request-ID', (string) str()->uuid());
    }
}

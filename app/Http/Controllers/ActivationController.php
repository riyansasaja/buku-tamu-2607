<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActivationPasswordRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserActivationToken;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ActivationController extends Controller
{
    public function show(string $token): View|Response
    {
        return $this->validToken($token) ? view('auth.activate', compact('token')) : response()->view('auth.activation-unavailable', [], 404);
    }

    public function store(StoreActivationPasswordRequest $request, string $token): View|Response
    {
        $activated = DB::transaction(function () use ($request, $token): ?User {
            $record = UserActivationToken::query()->where('token_hash', hash('sha256', $token))->lockForUpdate()->first();
            if (! $this->usable($record)) {
                return null;
            }
            $user = User::query()->whereKey($record->user_id)->lockForUpdate()->first();
            if ($user === null) {
                return null;
            }
            $user->update(['password' => Hash::make((string) $request->validated('password')), 'activated_at' => now()]);
            $record->update(['used_at' => now()]);
            AuditLog::query()->create(['actor_type' => 'activation_link', 'action' => 'admin.activated', 'auditable_type' => User::class, 'auditable_id' => $user->id, 'metadata' => [], 'request_id' => (string) str()->uuid()]);

            return $user;
        }, 3);

        return $activated ? view('auth.activation-complete') : response()->view('auth.activation-unavailable', [], 404);
    }

    private function validToken(string $plain): bool
    {
        return $this->usable(UserActivationToken::query()->where('token_hash', hash('sha256', $plain))->first());
    }

    private function usable(?UserActivationToken $token): bool
    {
        return $token !== null && $token->used_at === null && $token->revoked_at === null && $token->expires_at->isFuture();
    }
}

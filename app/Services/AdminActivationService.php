<?php

namespace App\Services;

use App\Jobs\SendAdminActivationWhatsApp;
use App\Models\User;
use App\Models\UserActivationToken;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminActivationService
{
    public function issue(User $user): void
    {
        DB::transaction(function () use ($user): void {
            UserActivationToken::query()->where('user_id', $user->id)->whereNull('used_at')->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $plain = Str::random(64);
            $token = UserActivationToken::query()->create([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $plain),
                'expires_at' => now()->addHours(24),
            ]);
            SendAdminActivationWhatsApp::dispatch($user->id, $token->id, Crypt::encryptString($plain));
        });
    }
}

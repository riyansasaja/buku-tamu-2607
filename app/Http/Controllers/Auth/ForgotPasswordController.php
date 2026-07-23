<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminPasswordResetService;
use App\Support\WhatsAppNumber;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ForgotPasswordController extends Controller
{
    public function show(): Response
    {
        return response()->view('auth.forgot-password')->header('Cache-Control', 'no-store, max-age=0')->header('X-Request-ID', (string) str()->uuid());
    }

    public function store(Request $request, AdminPasswordResetService $resets): Response
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = mb_strtolower(trim((string) $validated['email']));
        $user = User::query()->where('email', $email)->where('role', UserRole::Admin)->where('is_active', true)->whereNotNull('activated_at')->first();
        if ($user !== null && WhatsAppNumber::isValid($user->whatsapp)) {
            $resets->issue($user);
        }

        return response()->view('auth.forgot-password-sent')->header('Cache-Control', 'no-store, max-age=0')->header('X-Request-ID', (string) str()->uuid());
    }
}

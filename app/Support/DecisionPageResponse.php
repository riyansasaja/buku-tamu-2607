<?php

namespace App\Support;

use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class DecisionPageResponse
{
    public static function make(View $view, int $status, string $requestId): Response
    {
        return response($view, $status, [
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
            'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-Request-ID' => $requestId,
            'Content-Security-Policy' => self::contentSecurityPolicy(),
        ]);
    }

    private static function contentSecurityPolicy(): string
    {
        return "default-src 'self'; "
            ."img-src 'self' data:; "
            ."style-src 'self' 'unsafe-inline'; "
            ."script-src 'self'; "
            ."connect-src 'self'; "
            ."base-uri 'none'; frame-ancestors 'none'; form-action 'self'";
    }
}

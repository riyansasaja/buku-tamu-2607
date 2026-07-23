<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiClientKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = config('api.client_key');
        $provided = $request->header('X-Client-Key');

        if (! is_string($configured) || $configured === '') {
            return $this->error($request, 'API belum dikonfigurasi.', 503);
        }

        if (! is_string($provided) || ! hash_equals($configured, $provided)) {
            return $this->error($request, 'Kredensial API tidak valid.', 401);
        }

        return $next($request);
    }

    private function error(Request $request, string $message, int $status): Response
    {
        return response()->json([
            'message' => $message,
            'request_id' => $request->attributes->get('request_id'),
        ], $status);
    }
}

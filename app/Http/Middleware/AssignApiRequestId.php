<?php

namespace App\Http\Middleware;

use App\Exceptions\IdempotencyConflictException;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class AssignApiRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $supplied = $request->header('X-Request-ID');
        $requestId = is_string($supplied) && preg_match('/^[A-Za-z0-9._-]{1,100}$/', $supplied)
            ? $supplied
            : (string) str()->uuid();
        $request->attributes->set('request_id', $requestId);

        try {
            $response = $next($request);
        } catch (ValidationException $exception) {
            $response = $this->error('Data yang diberikan tidak valid.', 422, $requestId, $exception->errors());
        } catch (IdempotencyConflictException $exception) {
            $response = $this->error($exception->getMessage(), 409, $requestId);
        } catch (ModelNotFoundException) {
            $response = $this->error('Data tidak ditemukan.', 404, $requestId);
        } catch (HttpExceptionInterface $exception) {
            $status = $exception->getStatusCode();
            $message = match ($status) {
                403 => 'Tautan tidak valid atau sudah kedaluwarsa.',
                404 => 'Data tidak ditemukan.',
                429 => 'Terlalu banyak permintaan. Silakan coba kembali nanti.',
                default => $exception->getMessage() ?: 'Permintaan tidak dapat diproses.',
            };
            $response = $this->error($message, $status, $requestId);
        } catch (Throwable $exception) {
            report($exception);
            $response = $this->error('Terjadi kesalahan pada server.', 500, $requestId);
        }

        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    /** @param array<string, array<int, string>>|null $errors */
    private function error(string $message, int $status, string $requestId, ?array $errors = null): JsonResponse
    {
        return response()->json(array_filter([
            'message' => $message,
            'errors' => $errors,
            'request_id' => $requestId,
        ], static fn (mixed $value): bool => $value !== null), $status);
    }
}

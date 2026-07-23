<?php

namespace App\Services;

use App\Data\VisitCreationResult;
use App\Enums\VisitStatus;
use App\Exceptions\IdempotencyConflictException;
use App\Models\Visit;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class VisitCreationService
{
    /** @param array<string, mixed> $data */
    public function create(array $data, UploadedFile $photo): VisitCreationResult
    {
        $keyHash = hash('sha256', $data['idempotency_key']);
        $fingerprint = $this->fingerprint($data, $photo);
        $existing = Visit::query()->where('idempotency_key_hash', $keyHash)->first();

        if ($existing) {
            return $this->replayOrConflict($existing, $fingerprint);
        }

        $extension = match ($photo->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new RuntimeException('Tipe foto tidak didukung.'),
        };
        $path = 'visits/'.now()->format('Y/m').'/'.Str::uuid().'.'.$extension;

        if (! Storage::disk('local')->putFileAs(dirname($path), $photo, basename($path))) {
            throw new RuntimeException('Foto tidak dapat disimpan.');
        }

        try {
            $visit = DB::transaction(fn (): Visit => Visit::query()->create([
                'visit_number' => 'BTM-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'employee_id' => $data['employee_id'],
                'guest_name' => $data['guest_name'],
                'address' => $data['address'],
                'guest_whatsapp' => $data['guest_whatsapp'],
                'visit_purpose' => $data['visit_purpose'],
                'photo_path' => $path,
                'photo_mime_type' => $photo->getMimeType(),
                'status' => VisitStatus::Pending,
                'arrived_at' => now(),
                'idempotency_key_hash' => $keyHash,
                'request_fingerprint' => $fingerprint,
            ]));
        } catch (QueryException $exception) {
            Storage::disk('local')->delete($path);
            $existing = Visit::query()->where('idempotency_key_hash', $keyHash)->first();
            if ($existing) {
                return $this->replayOrConflict($existing, $fingerprint);
            }
            throw $exception;
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return new VisitCreationResult($visit, false);
    }

    /** @param array<string, mixed> $data */
    private function fingerprint(array $data, UploadedFile $photo): string
    {
        return hash('sha256', json_encode([
            'guest_name' => $data['guest_name'],
            'address' => $data['address'],
            'guest_whatsapp' => $data['guest_whatsapp'],
            'employee_id' => (int) $data['employee_id'],
            'visit_purpose' => $data['visit_purpose'],
            'photo_sha256' => hash_file('sha256', $photo->getRealPath()),
        ], JSON_THROW_ON_ERROR));
    }

    private function replayOrConflict(Visit $visit, string $fingerprint): VisitCreationResult
    {
        if (! hash_equals($visit->request_fingerprint, $fingerprint)) {
            throw new IdempotencyConflictException;
        }

        return new VisitCreationResult($visit, true);
    }
}

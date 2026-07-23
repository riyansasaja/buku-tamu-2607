<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisitPhotoController extends Controller
{
    public function __invoke(Visit $visit): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($visit->photo_path), 404);
        $extension = match ($visit->photo_mime_type) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'bin',
        };

        return Storage::disk('local')->response(
            $visit->photo_path,
            $visit->visit_number.'.'.$extension,
            [
                'Content-Type' => $visit->photo_mime_type,
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline',
        );
    }
}

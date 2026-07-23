<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class SecureImage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }
        $handle = @fopen($value->getRealPath(), 'rb');
        if ($handle === false) {
            $fail('Foto tidak dapat diperiksa keamanannya.');

            return;
        }
        $sample = (string) fread($handle, 1024 * 1024);
        fclose($handle);
        $lower = strtolower($sample);
        if (str_starts_with($sample, 'MZ') || str_starts_with($sample, "\x7fELF") || str_contains($lower, '<?php') || str_contains($lower, '<script')) {
            $fail('Foto ditolak oleh pemeriksaan keamanan.');
        }
    }
}

<?php

namespace App\Support;

final class WhatsAppNumber
{
    public static function normalize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $number = preg_replace('/[^0-9+]/', '', trim($value));
        if (! is_string($number)) {
            return null;
        }

        $number = ltrim($number, '+');
        if (str_starts_with($number, '0')) {
            $number = '62'.substr($number, 1);
        }

        return preg_match('/^[1-9][0-9]{8,14}$/', $number) === 1 ? $number : null;
    }

    public static function isValid(?string $value): bool
    {
        return self::normalize($value) !== null;
    }
}

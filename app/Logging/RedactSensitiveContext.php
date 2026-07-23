<?php

namespace App\Logging;

use Illuminate\Log\Logger as IlluminateLogger;
use Monolog\Logger;
use Monolog\LogRecord;

class RedactSensitiveContext
{
    private const SENSITIVE_KEYS = ['password', 'password_confirmation', 'token', 'authorization', 'api_key', 'client_key', 'whatsapp', 'guest_whatsapp', 'notification_contact', 'address', 'photo_path', 'comment'];

    public function __invoke(IlluminateLogger $logger): void
    {
        $monolog = $logger->getLogger();
        if (! $monolog instanceof Logger) {
            return;
        }

        $monolog->pushProcessor(function (LogRecord $record): LogRecord {
            return $record->with(context: $this->redact($record->context), extra: $this->redact($record->extra));
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->redact($value);
            }
        }

        return $data;
    }
}

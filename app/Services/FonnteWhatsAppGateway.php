<?php

namespace App\Services;

use App\Contracts\WhatsAppGateway;
use App\Data\WhatsAppSendResult;
use App\Exceptions\WhatsAppDeliveryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class FonnteWhatsAppGateway implements WhatsAppGateway
{
    public function send(string $target, string $message): WhatsAppSendResult
    {
        $url = config('services.fonnte.url');
        $token = config('services.fonnte.token');
        if (! is_string($url) || $url === '' || ! is_string($token) || $token === '') {
            throw new WhatsAppDeliveryException('configuration_missing');
        }

        try {
            $response = Http::asForm()
                ->withHeaders(['Authorization' => $token])
                ->connectTimeout(5)
                ->timeout((int) config('services.fonnte.timeout', 15))
                ->post($url, [
                    'target' => $target,
                    'message' => $message,
                    'countryCode' => '0',
                    'connectOnly' => true,
                    'preview' => false,
                ]);
        } catch (ConnectionException) {
            throw new WhatsAppDeliveryException('connection_failed');
        } catch (Throwable) {
            throw new WhatsAppDeliveryException('provider_error');
        }

        if (! $response->successful()) {
            throw new WhatsAppDeliveryException('http_'.$response->status());
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new WhatsAppDeliveryException('invalid_response');
        }

        $successful = ($data['status'] ?? $data['Status'] ?? false) === true;
        if (! $successful) {
            throw new WhatsAppDeliveryException($this->safeReason($data['reason'] ?? null));
        }

        $ids = $data['id'] ?? [];
        $messageId = is_array($ids) && isset($ids[0]) ? (string) $ids[0] : '';

        return new WhatsAppSendResult($messageId, (string) ($data['requestid'] ?? ''));
    }

    private function safeReason(mixed $reason): string
    {
        if (! is_string($reason)) {
            return 'rejected';
        }

        return match (strtolower(trim($reason))) {
            'token invalid' => 'invalid_token',
            'input invalid' => 'invalid_input',
            'target invalid' => 'invalid_target',
            'insufficient quota' => 'insufficient_quota',
            'device disconnected' => 'device_disconnected',
            default => 'rejected',
        };
    }
}

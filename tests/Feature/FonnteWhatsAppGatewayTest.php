<?php

namespace Tests\Feature;

use App\Exceptions\WhatsAppDeliveryException;
use App\Services\FonnteWhatsAppGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FonnteWhatsAppGatewayTest extends TestCase
{
    public function test_gateway_sends_secure_form_request_and_parses_success(): void
    {
        config([
            'services.fonnte.url' => 'https://api.fonnte.test/send',
            'services.fonnte.token' => 'test-secret-token',
            'services.fonnte.timeout' => 10,
        ]);
        Http::fake(['api.fonnte.test/*' => Http::response([
            'status' => true,
            'id' => ['message-123'],
            'requestid' => 9876,
        ])]);

        $result = app(FonnteWhatsAppGateway::class)->send('6281234567890', 'Pesan pengujian');

        $this->assertSame('message-123', $result->messageId);
        $this->assertSame('9876', $result->requestId);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.fonnte.test/send'
                && $request->hasHeader('Authorization', 'test-secret-token')
                && $request['target'] === '6281234567890'
                && $request['message'] === 'Pesan pengujian'
                && $request['countryCode'] === '0'
                && $request['connectOnly'] === true
                && $request['preview'] === false;
        });
    }

    public function test_gateway_maps_provider_rejection_to_safe_error_code(): void
    {
        config([
            'services.fonnte.url' => 'https://api.fonnte.test/send',
            'services.fonnte.token' => 'test-secret-token',
        ]);
        Http::fake(['api.fonnte.test/*' => Http::response([
            'status' => false,
            'reason' => 'target invalid',
            'requestid' => 123,
        ])]);

        try {
            app(FonnteWhatsAppGateway::class)->send('999123', 'Pesan');
            $this->fail('Exception tidak dilempar.');
        } catch (WhatsAppDeliveryException $exception) {
            $this->assertSame('invalid_target', $exception->errorCode);
            $this->assertStringNotContainsString('test-secret-token', $exception->getMessage());
            $this->assertStringNotContainsString('999123', $exception->getMessage());
        }
    }
}

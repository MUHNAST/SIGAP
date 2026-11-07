<?php

namespace Tests\Unit\Services;

use App\Exceptions\WhatsAppDeliveryException;
use App\Services\WhatsAppService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WhatsAppServiceTest extends TestCase
{
    public function test_send_returns_false_when_no_token_is_configured(): void
    {
        config([
            'services.fonnte.token_1' => null,
            'services.fonnte.token_2' => null,
        ]);
        Http::fake();

        $result = app(WhatsAppService::class)->send(
            '081234567890',
            'Pesan uji'
        );

        $this->assertFalse($result);
        Http::assertNothingSent();
    }

    public function test_send_or_fail_classifies_missing_fonnte_configuration(): void
    {
        config([
            'services.fonnte.token_1' => null,
            'services.fonnte.token_2' => null,
        ]);
        Http::fake();

        try {
            app(WhatsAppService::class)->sendOrFail(
                '081234567890',
                'Private message'
            );
            $this->fail('Missing Fonnte configuration should be reported.');
        } catch (WhatsAppDeliveryException $exception) {
            $this->assertSame('configuration_missing', $exception->failureType);
            $this->assertNull($exception->httpStatus);
        }

        Http::assertNothingSent();
    }

    #[DataProvider('phoneFormats')]
    public function test_send_normalizes_phone_before_sending(
        string $input,
        string $expected
    ): void {
        config([
            'services.fonnte.token_1' => 'test-token',
            'services.fonnte.token_2' => null,
        ]);
        Http::fake([
            '*' => Http::response(['status' => true], 200),
        ]);

        $result = app(WhatsAppService::class)->send($input, 'Pesan uji');

        $this->assertTrue($result);
        Http::assertSent(
            fn (ClientRequest $request): bool =>
                $request['target'] === $expected
                && $request['message'] === 'Pesan uji'
                && $request->hasHeader('Authorization', 'test-token')
        );
    }

    public static function phoneFormats(): iterable
    {
        yield 'legacy 620 prefix' => ['620812345678', '62812345678'];
        yield 'local zero prefix' => ['081234567890', '6281234567890'];
        yield 'international prefix' => ['6281234567890', '6281234567890'];
        yield 'local without prefix' => ['81234567890', '6281234567890'];
    }

    public function test_send_returns_false_when_provider_rejects_message(): void
    {
        config([
            'services.fonnte.token_1' => 'test-token',
            'services.fonnte.token_2' => null,
        ]);
        Http::fake([
            '*' => Http::response(['status' => false], 200),
        ]);

        $result = app(WhatsAppService::class)->send(
            '081234567890',
            'Pesan uji'
        );

        $this->assertFalse($result);
        Http::assertSentCount(1);
    }

    public function test_send_tries_next_token_when_provider_rejects_first_token(): void
    {
        config([
            'services.fonnte.token_1' => 'first-test-token',
            'services.fonnte.token_2' => 'second-test-token',
        ]);
        Http::fakeSequence()
            ->push(['status' => false], 200)
            ->push(['status' => true], 200);

        $result = app(WhatsAppService::class)->send(
            '081234567890',
            'Pesan uji'
        );

        $this->assertTrue($result);
        Http::assertSentCount(2);
        Http::assertSent(
            fn (ClientRequest $request): bool =>
                $request->hasHeader('Authorization', 'second-test-token')
        );
    }

    public function test_send_or_fail_exposes_http_and_provider_failure_details(): void
    {
        config([
            'services.fonnte.token_1' => 'test-token',
            'services.fonnte.token_2' => null,
        ]);
        Http::fake([
            '*' => Http::response(['status' => false], 200),
        ]);

        try {
            app(WhatsAppService::class)->sendOrFail(
                '081234567890',
                'Private message'
            );
            $this->fail('A failed Fonnte response should throw a delivery exception.');
        } catch (WhatsAppDeliveryException $exception) {
            $this->assertSame('provider_rejected', $exception->failureType);
            $this->assertSame(200, $exception->httpStatus);
            $this->assertStringNotContainsString('Private message', $exception->getMessage());
        }
    }

    public function test_send_or_fail_classifies_unsuccessful_http_status(): void
    {
        config([
            'services.fonnte.token_1' => 'test-token',
            'services.fonnte.token_2' => null,
        ]);
        Http::fake([
            '*' => Http::response([], 503),
        ]);

        try {
            app(WhatsAppService::class)->sendOrFail(
                '081234567890',
                'Private message'
            );
            $this->fail('An unsuccessful HTTP response should throw.');
        } catch (WhatsAppDeliveryException $exception) {
            $this->assertSame('http_error', $exception->failureType);
            $this->assertSame(503, $exception->httpStatus);
        }
    }

    public function test_send_or_fail_classifies_connection_timeout_without_exposing_exception_text(): void
    {
        config([
            'services.fonnte.token_1' => 'test-token',
            'services.fonnte.token_2' => null,
        ]);
        Http::fake(function () {
            throw new ConnectionException(
                'Connection timed out; sensitive detail'
            );
        });

        try {
            app(WhatsAppService::class)->sendOrFail(
                '081234567890',
                'Private message'
            );
            $this->fail('A connection timeout should throw a delivery exception.');
        } catch (WhatsAppDeliveryException $exception) {
            $this->assertSame('timeout', $exception->failureType);
            $this->assertNull($exception->httpStatus);
            $this->assertStringNotContainsString('sensitive detail', $exception->getMessage());
        }
    }
}

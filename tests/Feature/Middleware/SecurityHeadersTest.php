<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    private SecurityHeaders $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SecurityHeaders();
    }

    /**
     * Memeriksa penambahan header keamanan standar pada request HTTP biasa.
     */
    public function test_security_headers_ditambahkan_pada_request_http(): void
    {
        $request = Request::create('http://localhost/test', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('Content');
        });

        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertSame('geolocation=(), camera=(), microphone=()', $response->headers->get('Permissions-Policy'));
        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    /**
     * Memeriksa penambahan header Strict-Transport-Security ketika request aman (HTTPS).
     */
    public function test_hsts_ditambahkan_pada_request_https(): void
    {
        $request = Request::create('https://localhost/test', 'GET', [], [], [], [
            'HTTPS' => 'on',
        ]);

        $response = $this->middleware->handle($request, function () {
            return new Response('Secure Content');
        });

        $this->assertSame('max-age=31536000; includeSubDomains', $response->headers->get('Strict-Transport-Security'));
    }
}

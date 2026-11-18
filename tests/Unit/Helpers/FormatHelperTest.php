<?php

namespace Tests\Unit\Helpers;

use App\Helpers\DeviceHelper;
use Illuminate\Http\Request;
use Tests\TestCase as BaseTestCase;

class FormatHelperTest extends BaseTestCase
{
    public function test_nip_and_rupiah_formatters_handle_blank_valid_and_numeric_inputs(): void
    {
        $this->assertSame('-', format_nip(null));
        $this->assertSame('-', format_nip(''));
        $this->assertSame('12345', format_nip('12A345'));
        $this->assertSame('19740120 200604 1 002', format_nip('197401202006041002'));
        $this->assertSame('Rp 5.200.000', format_rupiah(5200000));
        $this->assertSame('5.200.000', format_rupiah(5200000, false));
        $this->assertSame('Rp 0', format_rupiah(null));
        $this->assertSame('Rp -1.250', format_rupiah(-1250));
    }

    public function test_terbilang_handles_each_numeric_scale_and_negative_values(): void
    {
        $this->assertSame(' ', terbilang(0));
        $this->assertSame(' sebelas', terbilang(11));
        $this->assertSame(' lima belas', terbilang(15));
        $this->assertSame(' dua puluh tiga', terbilang(23));
        $this->assertSame(' seratus ', terbilang(100));
        $this->assertSame(' seratus dua puluh lima', terbilang(125));
        $this->assertSame(' seribu ', terbilang(1000));
        $this->assertSame(' dua ribu dua puluh empat', terbilang(2024));
        $this->assertSame(' satu juta ', terbilang(1000000));
        $this->assertSame(' satu milyar ', terbilang(1000000000));
        $this->assertSame(' satu triliun ', terbilang(1000000000000));
        $this->assertSame(' lima', terbilang(-5));
    }

    public function test_device_fingerprint_uses_the_four_request_headers(): void
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Browser/1.0',
            'HTTP_ACCEPT_LANGUAGE' => 'id-ID',
            'HTTP_SEC_CH_UA' => '"Browser";v="1"',
            'HTTP_SEC_CH_UA_PLATFORM' => '"Windows"',
        ]);
        $this->app->instance('request', $request);

        $expected = hash(
            'sha256',
            'Browser/1.0|id-ID|"Browser";v="1"|"Windows"'
        );
        $fingerprint = DeviceHelper::fingerprint();

        $this->assertSame($expected, $fingerprint);
        $this->assertSame($fingerprint, DeviceHelper::fingerprint());

        $request->headers->set('Accept-Language', 'en-US');
        $this->assertNotSame($fingerprint, DeviceHelper::fingerprint());
    }
}

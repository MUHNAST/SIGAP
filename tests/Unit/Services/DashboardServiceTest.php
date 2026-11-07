<?php

namespace Tests\Unit\Services;

use App\Repositories\DashboardRepository;
use App\Services\DashboardService;
use Carbon\Carbon;
use Mockery;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $repository = Mockery::mock(DashboardRepository::class);
        $this->service = new DashboardService($repository);
    }

    /**
     * Memeriksa percabangan kondisi formatSchedule ketika tanggal bernilai null.
     */
    public function test_format_schedule_mengembalikan_belum_dijadwalkan_jika_tanggal_kosong(): void
    {
        $reflection = new \ReflectionClass(DashboardService::class);
        $method = $reflection->getMethod('formatSchedule');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, null);

        $this->assertSame('Belum dijadwalkan', $result['status']);
        $this->assertNull($result['tanggal']);
        $this->assertSame(0, $result['tahun_sisa']);
        $this->assertSame(0, $result['bulan_sisa']);
        $this->assertSame(0, $result['hari_sisa']);
    }

    /**
     * Memeriksa percabangan kondisi formatSchedule ketika tanggal di masa lalu (< today).
     */
    public function test_format_schedule_mengembalikan_terlambat_jika_tanggal_lampau(): void
    {
        $pastDate = Carbon::today()->subDays(5)->format('Y-m-d');

        $reflection = new \ReflectionClass(DashboardService::class);
        $method = $reflection->getMethod('formatSchedule');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $pastDate);

        $this->assertSame('Terlambat', $result['status']);
        $this->assertSame(0, $result['tahun_sisa']);
        $this->assertSame(0, $result['bulan_sisa']);
        $this->assertSame(0, $result['hari_sisa']);
    }

    /**
     * Memeriksa percabangan kondisi formatSchedule ketika tanggal tepat hari ini.
     */
    public function test_format_schedule_mengembalikan_hari_ini_jika_tanggal_sama_dengan_hari_ini(): void
    {
        $today = Carbon::today()->format('Y-m-d');

        $reflection = new \ReflectionClass(DashboardService::class);
        $method = $reflection->getMethod('formatSchedule');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $today);

        $this->assertSame('Hari Ini', $result['status']);
        $this->assertSame(0, $result['tahun_sisa']);
        $this->assertSame(0, $result['bulan_sisa']);
        $this->assertSame(0, $result['hari_sisa']);
    }

    /**
     * Memeriksa percabangan kondisi formatSchedule ketika tanggal di masa depan (> today).
     */
    public function test_format_schedule_mengembalikan_menunggu_dengan_sisa_waktu_jika_tanggal_masa_depan(): void
    {
        $futureDate = Carbon::today()->addYears(1)->addMonths(2)->addDays(10);

        $reflection = new \ReflectionClass(DashboardService::class);
        $method = $reflection->getMethod('formatSchedule');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $futureDate->format('Y-m-d'));

        $this->assertSame('Menunggu', $result['status']);
        $this->assertSame(1, $result['tahun_sisa']);
        $this->assertSame(2, $result['bulan_sisa']);
        $this->assertSame(10, $result['hari_sisa']);
    }
}


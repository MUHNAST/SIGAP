<?php

namespace Tests\Unit\Services;

use App\Models\Employee;
use App\Models\Rank;
use App\Models\Salary;
use App\Models\User;
use App\Repositories\DashboardRepository;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;
    private DashboardRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(DashboardRepository::class);
        $this->service = new DashboardService($this->repository);
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

    public function test_get_admin_dashboard_data_uses_selected_years_and_user_scope(): void
    {
        $user = new User(['role' => 'admin']);
        $this->expectDashboardRepositoryCalls($user, 2025, 2024);

        $data = $this->service->getAdminDashboardData($user, 2025, 2024);

        $this->assertSame(2025, $data['tahunReminder']);
        $this->assertSame(2024, $data['tahunRiwayat']);
        $this->assertSame(['2025'], $data['tahunList']);
        $this->assertSame(['totalPNS' => 1], $data['totalPNS']);
        $this->assertSame(['chartReminder' => 2025], $data['chartReminder']);
        $this->assertSame(['chartRiwayat' => 2024], $data['chartRiwayat']);
    }

    public function test_get_admin_dashboard_data_defaults_both_years_to_current_year(): void
    {
        $year = now()->year;
        $user = new User(['role' => 'super_admin']);
        $this->expectDashboardRepositoryCalls($user, $year, $year);

        $data = $this->service->getAdminDashboardData($user);

        $this->assertSame($year, $data['tahunReminder']);
        $this->assertSame($year, $data['tahunRiwayat']);
        $this->assertSame(['chartReminder' => $year], $data['chartReminder']);
        $this->assertSame(['chartRiwayat' => $year], $data['chartRiwayat']);
    }

    public function test_get_public_dashboard_data_caches_results_and_omits_private_feeds(): void
    {
        Cache::flush();
        $this->expectDashboardRepositoryCalls(null, now()->year, now()->year);

        $first = $this->service->getPublicDashboardData();
        $second = $this->service->getPublicDashboardData();

        $this->assertSame($first, $second);
        $this->assertSame(now()->year, $first['tahunReminder']);
        $this->assertSame(now()->year, $first['tahunRiwayat']);
        $this->assertSame([], $first['chartPangkat']);
        $this->assertTrue($first['aktivitas']->isEmpty());
        $this->assertTrue($first['notifikasi']->isEmpty());
    }

    public function test_get_user_dashboard_data_handles_missing_employee(): void
    {
        $user = new User(['nip' => 'NO-EMPLOYEE']);
        $this->repository->shouldReceive('notifikasiPengguna')
            ->once()
            ->with($user)
            ->andReturn(collect());
        $this->repository->shouldReceive('aktivitasPengguna')
            ->once()
            ->with($user)
            ->andReturn(collect());

        $data = $this->service->getUserDashboardData($user);

        $this->assertNull($data['employee']);
        $this->assertFalse($data['sudahPensiun']);
        $this->assertFalse($data['masaKerjaMaksimum']);
        $this->assertNull($data['maksimumMasaKerja']);
        $this->assertSame('Belum dijadwalkan', $data['kgb']['status']);
        $this->assertSame('Belum dijadwalkan', $data['promotion']['status']);
        $this->assertSame('Belum dijadwalkan', $data['retirement']['status']);
    }

    public function test_get_user_dashboard_data_checks_retirement_schedule_and_pns_salary_limit(): void
    {
        $rank = Rank::create([
            'kode' => 'DASH-PNS',
            'nama' => 'Pangkat Dashboard',
            'urutan_pangkat' => 1,
        ]);
        Salary::create([
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 5,
            'gaji_pokok' => 1000000,
            'status' => true,
        ]);
        Employee::create([
            'nip' => '199001012020011098',
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 5,
            'tmt_kgb_baru' => today()->toDateString(),
            'tmt_pangkat_baru' => today()->subDay()->toDateString(),
            'tmt_pensiun' => today()->toDateString(),
        ]);
        $user = new User(['nip' => '199001012020011098']);
        $this->repository->shouldReceive('notifikasiPengguna')
            ->once()
            ->with($user)
            ->andReturn(collect(['notice']));
        $this->repository->shouldReceive('aktivitasPengguna')
            ->once()
            ->with($user)
            ->andReturn(collect(['activity']));

        $data = $this->service->getUserDashboardData($user);

        $this->assertTrue($data['sudahPensiun']);
        $this->assertTrue($data['masaKerjaMaksimum']);
        $this->assertSame(5, $data['maksimumMasaKerja']);
        $this->assertSame('Hari Ini', $data['kgb']['status']);
        $this->assertSame('Terlambat', $data['promotion']['status']);
        $this->assertSame('Hari Ini', $data['retirement']['status']);
        $this->assertSame(['notice'], $data['notifikasi']->all());
        $this->assertSame(['activity'], $data['aktivitas']->all());
    }

    private function expectDashboardRepositoryCalls(
        ?User $user,
        int $reminderYear,
        int $historyYear
    ): void {
        $methods = [
            'availableYears',
            'pegawaiPensiun',
            'pegawaiAktif',
            'pegawaiNonaktif',
            'totalPNS',
            'totalPPPK',
            'naikPangkat',
            'kgb',
            'pensiun',
            'chartJenisPegawai',
            'ChartJenisKelamin',
            'chartPangkat',
            'chartGolonganPppk',
            'chartPendidikan',
            'chartStatus',
            'chartMasaKerja',
            'chartUsia',
        ];

        foreach ($methods as $method) {
            $this->repository->shouldReceive($method)
                ->once()
                ->with(...($user === null ? [] : [$user]))
                ->andReturn(match ($method) {
                    'availableYears' => ['2025'],
                    'totalPNS' => ['totalPNS' => 1],
                    default => [],
                });
        }

        $this->repository->shouldReceive('chartReminder')
            ->once()
            ->with($reminderYear, ...($user === null ? [] : [$user]))
            ->andReturn(['chartReminder' => $reminderYear]);
        $this->repository->shouldReceive('chartRiwayat')
            ->once()
            ->with($historyYear, ...($user === null ? [] : [$user]))
            ->andReturn(['chartRiwayat' => $historyYear]);

        if ($user !== null) {
            $this->repository->shouldReceive('aktivitas')
                ->once()->with($user)->andReturn([]);
            $this->repository->shouldReceive('notifikasi')
                ->once()->with($user)->andReturn([]);
        }
    }
}


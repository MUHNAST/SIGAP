<?php

namespace Tests\Unit\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\KgbHistory;
use App\Models\Rank;
use App\Models\Salary;
use App\Models\User;
use App\Repositories\ReportRepository;
use App\Services\NotificationService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $service;
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(ReportRepository::class);
        $this->service = new ReportService($this->repository);
    }

    /**
     * Memeriksa konversi format string rupiah ke integer.
     */
    public function test_rupiah_to_int_menghapus_karakter_non_digit_dan_mengubah_ke_integer(): void
    {
        $reflection = new \ReflectionClass(ReportService::class);
        $method = $reflection->getMethod('rupiahToInt');
        $method->setAccessible(true);

        $this->assertSame(3500000, $method->invoke($this->service, 'Rp 3.500.000'));
        $this->assertSame(4125600, $method->invoke($this->service, 'Rp. 4.125.600,-'));
        $this->assertSame(0, $method->invoke($this->service, null));
        $this->assertSame(0, $method->invoke($this->service, ''));
    }

    /**
     * Memeriksa validasi preview KGB menolak pegawai berstatus PPPK.
     */
    public function test_ensure_kgb_preview_menolak_pegawai_pppk(): void
    {
        $user = new User(['role' => 'admin']);
        $employee = new Employee(['status_pegawai' => 'PPPK', 'tmt_kgb_baru' => '2026-11-01']);

        $reflection = new \ReflectionClass(ReportService::class);
        $method = $reflection->getMethod('ensureKgbPreviewAvailable');
        $method->setAccessible(true);

        try {
            $method->invoke($this->service, $employee, $user);
            $this->fail('Seharusnya ditolak 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame('Preview surat KGB hanya tersedia untuk pegawai PNS.', $e->getMessage());
        }
    }

    /**
     * Memeriksa validasi preview KGB menolak pegawai yang belum memiliki TMT KGB baru.
     */
    public function test_ensure_kgb_preview_menolak_pegawai_tanpa_tmt_kgb_baru(): void
    {
        $user = new User(['role' => 'admin']);
        $employee = new Employee(['status_pegawai' => 'PNS', 'tmt_kgb_baru' => null]);

        $reflection = new \ReflectionClass(ReportService::class);
        $method = $reflection->getMethod('ensureKgbPreviewAvailable');
        $method->setAccessible(true);

        try {
            $method->invoke($this->service, $employee, $user);
            $this->fail('Seharusnya ditolak 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame('TMT KGB belum tersedia.', $e->getMessage());
        }
    }

    /**
     * Memeriksa validasi preview KGB menolak periode KGB yang sudah pernah diproses.
     */
    public function test_ensure_kgb_preview_menolak_jika_periode_sudah_pernah_diproses(): void
    {
        $user = new User(['role' => 'admin']);
        $employee = new Employee([
            'nip' => '198501012010011001',
            'status_pegawai' => 'PNS',
            'tmt_kgb_baru' => Carbon::today()->addMonth()->format('Y-m-d'),
        ]);

        $this->repository->shouldReceive('getExistingKgbHistory')
            ->once()
            ->andReturn(new KgbHistory());

        $reflection = new \ReflectionClass(ReportService::class);
        $method = $reflection->getMethod('ensureKgbPreviewAvailable');
        $method->setAccessible(true);

        try {
            $method->invoke($this->service, $employee, $user);
            $this->fail('Seharusnya ditolak 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame('Periode KGB ini sudah pernah diproses.', $e->getMessage());
        }
    }

    /**
     * Memeriksa kondisi batas tanggal preview: ditolak jika hari ini lebih dari 2 bulan sebelum TMT KGB.
     */
    public function test_ensure_kgb_preview_menolak_jika_hari_ini_terlalu_dini_lebih_dari_2_bulan_sebelum_tmt(): void
    {
        $user = new User(['role' => 'admin']);
        // TMT KGB adalah 3 bulan lagi (di luar batas 2 bulan)
        $tmt = Carbon::today()->addMonths(3);

        $employee = new Employee([
            'nip' => '198501012010011001',
            'status_pegawai' => 'PNS',
            'tmt_kgb_baru' => $tmt->format('Y-m-d'),
        ]);

        $this->repository->shouldReceive('getExistingKgbHistory')->once()->andReturnNull();

        $reflection = new \ReflectionClass(ReportService::class);
        $method = $reflection->getMethod('ensureKgbPreviewAvailable');
        $method->setAccessible(true);

        try {
            $method->invoke($this->service, $employee, $user);
            $this->fail('Seharusnya ditolak 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertStringContainsString('Preview hanya tersedia mulai dua bulan sebelum TMT KGB', $e->getMessage());
        }
    }

    /**
     * Memeriksa kondisi batas tanggal preview: lolos jika hari ini berada dalam rentang 2 bulan sebelum TMT KGB.
     */
    public function test_ensure_kgb_preview_lolos_jika_dalam_rentang_2_bulan(): void
    {
        $user = new User(['role' => 'admin']);
        // TMT KGB adalah 1 bulan lagi (dalam rentang 2 bulan)
        $tmt = Carbon::today()->addMonth();

        $employee = new Employee([
            'nip' => '198501012010011001',
            'status_pegawai' => 'PNS',
            'tmt_kgb_baru' => $tmt->format('Y-m-d'),
        ]);

        $this->repository->shouldReceive('getExistingKgbHistory')->once()->andReturnNull();

        $reflection = new \ReflectionClass(ReportService::class);
        $method = $reflection->getMethod('ensureKgbPreviewAvailable');
        $method->setAccessible(true);

        $method->invoke($this->service, $employee, $user);
        $this->assertTrue(true);
    }

    /**
     * Memeriksa penghitungan status KGB (Hari Ini, Terlambat, Menunggu) dan masa kerja baru pada prepareKgbInformation.
     */
    public function test_prepare_kgb_information_menghitung_status_dan_masa_kerja_baru(): void
    {
        $reflection = new \ReflectionClass(ReportService::class);
        $method = $reflection->getMethod('prepareKgbInformation');
        $method->setAccessible(true);

        $rank = Rank::create(['kode' => 'III/a', 'nama' => 'Penata Muda', 'urutan_pangkat' => 9]);

        // Kasus 1: TMT KGB adalah hari ini -> Status 'Hari Ini', masa kerja baru PNS = +2 tahun
        $empHariIni = new Employee([
            'status_pegawai' => 'PNS',
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 4,
            'bulan_masa_kerja' => 0,
            'tmt_kgb_lama' => Carbon::today()->subYears(2)->format('Y-m-d'),
            'tmt_kgb_baru' => Carbon::today()->format('Y-m-d'),
        ]);

        $res1 = $method->invoke($this->service, $empHariIni);
        $this->assertSame('Hari Ini', $res1->status_kgb);
        $this->assertSame('bg-green-500', $res1->badge_kgb);
        $this->assertSame(6, $res1->tahun_masa_kerja_baru); // 4 + 2 tahun
        $this->assertSame(0, $res1->hari);

        // Kasus 2: TMT KGB sudah lewat -> Status 'Terlambat'
        $empTerlambat = new Employee([
            'status_pegawai' => 'PNS',
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 4,
            'bulan_masa_kerja' => 0,
            'tmt_kgb_lama' => Carbon::today()->subYears(3)->format('Y-m-d'),
            'tmt_kgb_baru' => Carbon::today()->subDays(10)->format('Y-m-d'),
        ]);

        $res2 = $method->invoke($this->service, $empTerlambat);
        $this->assertSame('Terlambat', $res2->status_kgb);
        $this->assertSame('bg-red-500', $res2->badge_kgb);
        $this->assertLessThan(0, $res2->hari);

        // Kasus 3: TMT KGB masa depan -> Status 'Menunggu'
        $empMenunggu = new Employee([
            'status_pegawai' => 'PNS',
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 4,
            'bulan_masa_kerja' => 0,
            'tmt_kgb_lama' => Carbon::today()->subYear()->format('Y-m-d'),
            'tmt_kgb_baru' => Carbon::today()->addMonths(6)->format('Y-m-d'),
        ]);

        $res3 = $method->invoke($this->service, $empMenunggu);
        $this->assertSame('Menunggu', $res3->status_kgb);
        $this->assertSame('bg-blue-500', $res3->badge_kgb);
        $this->assertGreaterThan(0, $res3->hari);

        // Kasus 4: Pegawai PPPK -> masa kerja baru tidak bertambah 2 tahun saat kalkulasi preview
        $empPppk = new Employee([
            'status_pegawai' => 'PPPK',
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 2,
            'bulan_masa_kerja' => 0,
            'tmt_kgb_lama' => Carbon::today()->subYear()->format('Y-m-d'),
            'tmt_kgb_baru' => Carbon::today()->addMonths(6)->format('Y-m-d'),
        ]);

        $res4 = $method->invoke($this->service, $empPppk);
        $this->assertSame(2, $res4->tahun_masa_kerja_baru); // Tetap 2 tahun
    }

    /**
     * Memeriksa ensureOwnEmployee memastikan user hanya dapat mengakses NIP miliknya.
     */
    public function test_ensure_own_employee_memvalidasi_kesesuaian_nip_user(): void
    {
        $reflection = new \ReflectionClass(ReportService::class);
        $method = $reflection->getMethod('ensureOwnEmployee');
        $method->setAccessible(true);

        // 1. Role bukan user -> 403
        $admin = new User(['role' => 'admin', 'nip' => '123']);
        $this->actingAs($admin);
        try {
            $method->invoke($this->service, '123');
            $this->fail('Seharusnya ditolak 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // 2. User tanpa NIP -> 403
        $userTanpaNip = new User(['role' => 'user', 'nip' => null]);
        $this->actingAs($userTanpaNip);
        try {
            $method->invoke($this->service, '123');
            $this->fail('Seharusnya ditolak 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // 3. User mengakses NIP orang lain -> 403
        $userValid = new User(['role' => 'user', 'nip' => '123456789012345678']);
        $this->actingAs($userValid);
        try {
            $method->invoke($this->service, '999999999999999999');
            $this->fail('Seharusnya ditolak 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // 4. User mengakses NIP miliknya -> lolos
        $result = $method->invoke($this->service, '123456789012345678');
        $this->assertSame('123456789012345678', $result->nip);
    }
}


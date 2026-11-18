<?php

namespace Tests\Unit\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\KgbHistory;
use App\Models\Position;
use App\Models\Rank;
use App\Models\Salary;
use App\Models\User;
use App\Repositories\ReportRepository;
use App\Services\NotificationService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    private function reportEmployee(array $attributes = []): Employee
    {
        $today = Carbon::today();

        return new Employee(array_merge([
            'nip' => '198501012010011001',
            'status_pegawai' => 'PNS',
            'tahun_masa_kerja' => 4,
            'bulan_masa_kerja' => 0,
            'tmt_kgb_lama' => $today->copy()->subYears(2)->toDateString(),
            'tmt_kgb_baru' => $today->copy()->addMonth()->toDateString(),
        ], $attributes));
    }

    private function createPrintFixture(): array
    {
        $department = Department::create([
            'kode' => 'RPT-PRINT',
            'nama' => 'Unit Uji ReportService',
            'status' => true,
        ]);
        $rank = Rank::create([
            'kode' => 'III/a',
            'nama' => 'Pangkat Uji ReportService',
            'urutan_pangkat' => 9,
        ]);
        Salary::create([
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 4,
            'gaji_pokok' => 2_800_000,
        ]);
        Salary::create([
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 6,
            'gaji_pokok' => 3_100_000,
        ]);
        $user = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => $department->kode,
        ]);
        $employee = Employee::create([
            'nip' => '198501012010011099',
            'nama' => 'Pegawai Uji ReportService',
            'kode_unit_kerja' => $department->kode,
            'id_pangkat' => $rank->id,
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
            'tanggal_masuk' => '2010-01-01',
            'tahun_masa_kerja' => 4,
            'bulan_masa_kerja' => 3,
            'tmt_kgb_lama' => Carbon::today()->subYears(2)->toDateString(),
            'tmt_kgb_baru' => Carbon::today()->addMonthNoOverflow()->toDateString(),
        ]);

        return compact('department', 'rank', 'user', 'employee');
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

    public function test_get_employee_adds_empty_schedule_information_when_dates_are_missing(): void
    {
        $user = new User(['role' => 'admin']);
        $employee = new Employee([
            'nip' => '198501012010011010',
            'status_pegawai' => 'PNS',
        ]);
        $this->actingAs($user);
        $this->repository->shouldReceive('getEmployeeByNip')
            ->once()
            ->with($employee->nip, $user)
            ->andReturn($employee);

        $warnings = [];
        set_error_handler(static function (int $severity, string $message) use (&$warnings): bool {
            $warnings[] = [$severity, $message];

            return true;
        });
        try {
            $result = $this->service->getEmployee($employee->nip);
        } finally {
            restore_error_handler();
        }

        $this->assertSame($employee, $result);
        $this->assertSame('Belum tersedia', $result->status_kgb);
        $this->assertNull($result->tanggal_kgb);
        $this->assertNull($result->hari);
        $this->assertSame(0, $result->tahun_sisa);
        $this->assertSame(0, $result->bulan_sisa);
        $this->assertSame(0, $result->hari_sisa);
        $this->assertSame(0, $result->progress_kgb);
        $this->assertSame('bg-slate-500', $result->badge_kgb);
        foreach ([
            'tanggal_kgb',
            'hari',
            'tahun_sisa',
            'bulan_sisa',
            'hari_sisa',
            'progress_kgb',
            'status_kgb',
            'badge_kgb',
        ] as $attribute) {
            $this->assertArrayHasKey($attribute, $result->getAttributes());
        }
        $this->assertSame([], $warnings);
    }

    public function test_user_can_preview_their_own_eligible_kgb_employee(): void
    {
        $nip = '198501012010011011';
        $user = new User(['role' => 'user', 'nip' => $nip]);
        $employee = new Employee([
            'nip' => $nip,
            'status_pegawai' => 'PNS',
            'tahun_masa_kerja' => 4,
            'bulan_masa_kerja' => 3,
            'tmt_kgb_lama' => Carbon::today()->subYears(2)->toDateString(),
            'tmt_kgb_baru' => Carbon::today()->addMonth()->toDateString(),
        ]);
        $this->actingAs($user);
        $this->repository->shouldReceive('getEmployeeByNip')
            ->once()
            ->with($nip, $user)
            ->andReturn($employee);
        $this->repository->shouldReceive('getExistingKgbHistory')
            ->once()
            ->withArgs(
                fn (string $employeeNip, $tmt, User $actor): bool =>
                    $employeeNip === $nip
                    && $tmt->toDateString()
                        === $employee->tmt_kgb_baru->toDateString()
                    && $actor === $user
            )
            ->andReturnNull();

        $result = $this->service->getEmployeeForUserKgbPreview($nip);

        $this->assertSame($nip, $result->nip);
        $this->assertSame('Menunggu', $result->status_kgb);
        $this->assertSame(6, $result->tahun_masa_kerja_baru);
        $this->assertGreaterThan(0, $result->progress_kgb);
    }

    public function test_user_preview_availability_enforces_public_role_and_nip_guards(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15'));
        $user = new User(['role' => 'user', 'nip' => 123]);
        $employee = $this->reportEmployee([
            'nip' => '123',
            'tmt_kgb_baru' => '2026-12-15',
        ]);
        $this->actingAs($user);
        $this->repository->shouldReceive('getExistingKgbHistory')
            ->once()
            ->andReturnNull();

        $this->service->ensureUserKgbPreviewAvailable($employee);

        $employee->nip = '124';
        try {
            $this->service->ensureUserKgbPreviewAvailable($employee);
            $this->fail('A user must not validate another employee.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $admin = new User(['role' => 'admin', 'nip' => '123']);
        $this->actingAs($admin);
        $employee->nip = '123';
        try {
            $this->service->ensureUserKgbPreviewAvailable($employee);
            $this->fail('A non-user role must not use the user preview validator.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_user_print_wrapper_validates_ownership_and_delegates_to_shared_print_flow(): void
    {
        $nip = '198501012010011012';
        $user = new User(['role' => 'user', 'nip' => $nip]);
        $employee = new Employee(['nip' => $nip]);
        $history = new KgbHistory(['nip' => $nip]);
        $data = ['nomor_sk_baru' => '800/TEST/2026'];
        $service = Mockery::mock(ReportService::class, [$this->repository])
            ->makePartial();
        $service->shouldReceive('getEmployeeForUserKgbPreview')
            ->once()
            ->with($nip)
            ->andReturn($employee);
        $service->shouldReceive('printKgb')
            ->once()
            ->with($nip, $data)
            ->andReturn($history);
        $this->actingAs($user);

        $this->assertSame($history, $service->printKgbForUser($nip, $data));
    }

    public function test_print_kgb_for_user_remains_public(): void
    {
        $method = new \ReflectionMethod(ReportService::class, 'printKgbForUser');

        $this->assertTrue($method->isPublic());
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

        // 2a. User dengan NIP hanya whitespace dianggap belum memiliki NIP.
        $userWhitespaceNip = new User(['role' => 'user', 'nip' => ' ']);
        $this->actingAs($userWhitespaceNip);
        try {
            $method->invoke($this->service, ' ');
            $this->fail('NIP whitespace seharusnya ditolak.');
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

        // 5. NIP numerik tetap sesuai setelah normalisasi ke string.
        $numericNipUser = new User(['role' => 'user', 'nip' => 123]);
        $this->actingAs($numericNipUser);
        $numericResult = $method->invoke($this->service, '123');
        $this->assertSame(123, $numericResult->nip);
    }

    public function test_get_laporan_kgb_maps_rows_and_returns_empty_collection(): void
    {
        $user = new User(['role' => 'super_admin']);
        $employee = $this->reportEmployee([
            'tmt_kgb_lama' => null,
            'tmt_kgb_baru' => null,
        ]);
        $this->actingAs($user);
        $this->repository->shouldReceive('getLaporanKGB')
            ->twice()
            ->with(['search' => 'uji'], $user)
            ->andReturn(collect([$employee]), collect());

        $results = $this->service->getLaporanKGB(['search' => 'uji']);
        $empty = $this->service->getLaporanKGB(['search' => 'uji']);

        $this->assertSame($employee, $results->sole());
        $this->assertSame('Belum tersedia', $results->sole()->status_kgb);
        $this->assertTrue($empty->isEmpty());
    }

    public function test_get_laporan_kgb_for_user_maps_employee_data_and_returns_empty_collection(): void
    {
        $nip = '198501012010011071';
        $user = new User(['role' => 'user', 'nip' => $nip]);
        $employee = $this->reportEmployee([
            'nip' => $nip,
            'tmt_kgb_lama' => null,
            'tmt_kgb_baru' => null,
        ]);
        $this->actingAs($user);
        $this->repository->shouldReceive('getLaporanKGBForUser')
            ->twice()
            ->with($user, ['search' => 'uji'])
            ->andReturn(collect([$employee]), collect());

        $results = $this->service->getLaporanKGBForUser(['search' => 'uji']);
        $empty = $this->service->getLaporanKGBForUser(['search' => 'uji']);

        $this->assertSame($employee, $results->sole());
        $this->assertSame('Belum tersedia', $results->sole()->status_kgb);
        $this->assertTrue($empty->isEmpty());
    }

    public function test_get_employee_marks_schedule_unavailable_when_either_tmt_is_missing(): void
    {
        $user = new User(['role' => 'admin']);
        $oldTmtMissing = $this->reportEmployee([
            'nip' => '198501012010011081',
            'tmt_kgb_lama' => null,
            'tmt_kgb_baru' => '2027-01-01',
        ]);
        $newTmtMissing = $this->reportEmployee([
            'nip' => '198501012010011082',
            'tmt_kgb_lama' => '2025-01-01',
            'tmt_kgb_baru' => null,
        ]);
        $serviceYearsMissing = $this->reportEmployee([
            'nip' => '198501012010011083',
            'tahun_masa_kerja' => null,
            'bulan_masa_kerja' => null,
        ]);
        $this->actingAs($user);
        $this->repository->shouldReceive('getEmployeeByNip')
            ->times(3)
            ->withArgs(fn (string $nip, User $actor): bool =>
                in_array($nip, [
                    $oldTmtMissing->nip,
                    $newTmtMissing->nip,
                    $serviceYearsMissing->nip,
                ], true)
                && $actor === $user
            )
            ->andReturn($oldTmtMissing, $newTmtMissing, $serviceYearsMissing);

        foreach ([$oldTmtMissing, $newTmtMissing] as $employee) {
            $result = $this->service->getEmployee($employee->nip);

            $this->assertSame('Belum tersedia', $result->status_kgb);
            $this->assertNull($result->tanggal_kgb);
            $this->assertSame(0, $result->progress_kgb);
        }

        $normalized = $this->service->getEmployee($serviceYearsMissing->nip);

        $this->assertSame(0, $normalized->tahun_masa_kerja_lama);
        $this->assertSame(0, $normalized->bulan_masa_kerja_lama);
        $this->assertSame(2, $normalized->tahun_masa_kerja_baru);
        $this->assertSame(0, $normalized->bulan_masa_kerja_baru);
    }

    public function test_report_service_rejects_unauthenticated_access(): void
    {
        try {
            $this->service->getHistory();
            $this->fail('Report access requires an authenticated user.');
        } catch (HttpException $exception) {
            $this->assertSame(401, $exception->getStatusCode());
        }
    }

    public function test_get_employee_for_kgb_preview_accepts_inclusive_date_boundaries(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15'));
        $user = new User(['role' => 'admin']);
        $atStart = $this->reportEmployee([
            'nip' => '198501012010011021',
            'tmt_kgb_lama' => '2024-12-15',
            'tmt_kgb_baru' => Carbon::today()->addMonthsNoOverflow(2)->toDateString(),
        ]);
        $atTmt = $this->reportEmployee([
            'nip' => '198501012010011022',
            'tmt_kgb_lama' => '2024-10-15',
            'tmt_kgb_baru' => Carbon::today()->toDateString(),
        ]);
        $this->actingAs($user);
        $this->repository->shouldReceive('getEmployeeForKgbPreview')
            ->twice()
            ->withArgs(fn (string $nip, User $actor): bool =>
                in_array($nip, [$atStart->nip, $atTmt->nip], true)
                && $actor === $user
            )
            ->andReturn($atStart, $atTmt);
        $this->repository->shouldReceive('getExistingKgbHistory')
            ->twice()
            ->withArgs(fn (string $nip, Carbon $tmt, User $actor): bool =>
                in_array($nip, [$atStart->nip, $atTmt->nip], true)
                && $actor === $user
                && $tmt->toDateString() === (
                    $nip === $atStart->nip
                        ? Carbon::today()->addMonthsNoOverflow(2)->toDateString()
                        : Carbon::today()->toDateString()
                )
            )
            ->andReturnNull();

        $first = $this->service->getEmployeeForKgbPreview($atStart->nip);
        $this->assertSame($atStart->nip, $first->nip);

        $this->travelTo(Carbon::parse('2026-10-15'));
        $second = $this->service->getEmployeeForKgbPreview($atTmt->nip);
        $this->assertSame($atTmt->nip, $second->nip);
        $this->assertSame('Hari Ini', $second->status_kgb);
    }

    public function test_get_employee_for_kgb_preview_rejects_invalid_preview_cases(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15'));
        $user = new User(['role' => 'admin']);
        $cases = [
            [
                $this->reportEmployee([
                    'status_pegawai' => 'PPPK',
                    'tmt_kgb_baru' => '2026-11-15',
                ]),
                null,
                'Preview surat KGB hanya tersedia untuk pegawai PNS.',
            ],
            [
                $this->reportEmployee(['tmt_kgb_baru' => null]),
                null,
                'TMT KGB belum tersedia.',
            ],
            [
                $this->reportEmployee(['tmt_kgb_baru' => '2026-11-15']),
                new KgbHistory(),
                'Periode KGB ini sudah pernah diproses.',
            ],
            [
                $this->reportEmployee(['tmt_kgb_baru' => '2027-01-16']),
                null,
                'Preview hanya tersedia mulai dua bulan sebelum TMT KGB',
            ],
            [
                $this->reportEmployee(['tmt_kgb_baru' => '2026-10-14']),
                null,
                'Preview hanya tersedia mulai dua bulan sebelum TMT KGB',
            ],
        ];

        foreach ($cases as [$employee, $existingHistory, $message]) {
            $repository = Mockery::mock(ReportRepository::class);
            $repository->shouldReceive('getEmployeeForKgbPreview')
                ->once()
                ->with($employee->nip, $user)
                ->andReturn($employee);
            if ($employee->status_pegawai === 'PNS' && $employee->tmt_kgb_baru) {
                $repository->shouldReceive('getExistingKgbHistory')
                    ->once()
                    ->andReturn($existingHistory);
            }
            $service = new ReportService($repository);
            $this->actingAs($user);

            try {
                $service->getEmployeeForKgbPreview($employee->nip);
                $this->fail('An ineligible preview must be rejected.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
                $this->assertStringContainsString($message, $exception->getMessage());
            }
        }
    }

    public function test_history_and_camat_accessors_return_repository_data(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15'));
        $user = new User(['role' => 'admin']);
        $employee = $this->reportEmployee();
        $history = new KgbHistory([
            'id_pangkat' => null,
            'tmt_kgb_lama' => '2024-10-15',
            'tmt_kgb_baru' => '2026-10-15',
            'tahun_masa_kerja_lama' => 4,
            'bulan_masa_kerja_lama' => 0,
            'tahun_masa_kerja_baru' => 6,
            'bulan_masa_kerja_baru' => 0,
            'gaji_pokok_lama' => 2_800_000,
            'gaji_pokok_baru' => 3_100_000,
        ]);
        $history->setRelation('employee', $employee);
        $histories = collect([$history]);
        $camat = $this->reportEmployee(['nama' => 'Camat']);
        $this->actingAs($user);
        $this->repository->shouldReceive('getHistory')
            ->twice()
            ->with($user)
            ->andReturn($histories, collect());
        $this->repository->shouldReceive('getHistoryById')
            ->once()
            ->with(17, $user)
            ->andReturn($history);
        $this->repository->shouldReceive('getCamat')
            ->twice()
            ->andReturn($camat, null);

        $this->assertSame($histories, $this->service->getHistory());
        $this->assertTrue($this->service->getHistory()->isEmpty());
        $result = $this->service->getHistoryById(17);
        $this->assertSame($history, $result);
        $this->assertSame('Hari Ini', $result->employee->status_kgb);
        $this->assertSame($camat, $this->service->getCamat());
        $this->assertNull($this->service->getCamat());
    }

    public function test_existing_and_previous_history_handle_missing_dates_and_use_current_history_date(): void
    {
        $user = new User(['role' => 'admin']);
        $withoutTmt = $this->reportEmployee([
            'nip' => '198501012010011031',
            'tmt_kgb_baru' => null,
        ]);
        $withTmt = $this->reportEmployee([
            'nip' => '198501012010011032',
            'tmt_kgb_baru' => '2026-12-15',
        ]);
        $current = new KgbHistory([
            'tmt_kgb_baru' => '2026-10-15',
        ]);
        $current->id = 22;
        $previous = new KgbHistory(['nomor_sk_baru' => '800/PREV/2024']);
        $this->actingAs($user);
        $this->repository->shouldReceive('getEmployeeByNip')
            ->times(5)
            ->andReturn($withoutTmt, $withTmt, $withoutTmt, $withTmt, $withTmt);
        $this->repository->shouldReceive('getExistingKgbHistory')
            ->once()
            ->withArgs(fn (string $nip, Carbon $tmt, User $actor): bool =>
                $nip === $withTmt->nip
                && $tmt->toDateString() === '2026-12-15'
                && $actor === $user
            )
            ->andReturn($previous);
        $this->repository->shouldReceive('getPreviousHistory')
            ->twice()
            ->withArgs(fn (string $nip, User $actor, string $beforeTmt, ?int $historyId): bool =>
                $nip === $withTmt->nip
                && $actor === $user
                && in_array($beforeTmt, ['2026-10-15', '2026-12-15'], true)
                && $historyId === (
                    $beforeTmt === '2026-10-15'
                        ? 22
                        : null
                )
            )
            ->andReturn($previous);

        $warnings = [];
        set_error_handler(static function (int $severity, string $message) use (&$warnings): bool {
            $warnings[] = [$severity, $message];

            return true;
        });
        try {
            $this->assertNull($this->service->getExistingKgbHistory($withoutTmt->nip));
            $this->assertSame($previous, $this->service->getExistingKgbHistory($withTmt->nip));
            $this->assertNull($this->service->getPreviousHistory($withoutTmt));
            $this->assertSame($previous, $this->service->getPreviousHistory($withTmt, $current));
            $this->assertSame($previous, $this->service->getPreviousHistory($withTmt));
        } finally {
            restore_error_handler();
        }
        $this->assertSame([], $warnings);
    }

    public function test_prepare_kgb_information_computes_progress_at_each_tmt_boundary(): void
    {
        $this->travelTo(Carbon::parse('2026-05-01'));
        $today = Carbon::today();
        $cases = [
            [$today->copy()->addYear(), $today->copy()->addYears(3), 0, 'Menunggu', 'bg-blue-500'],
            [$today, $today->copy()->addYears(2), 0, 'Menunggu', 'bg-blue-500'],
            [$today->copy()->subYear(), $today->copy()->addYear(), 50, 'Menunggu', 'bg-blue-500'],
            [$today->copy()->subYears(2), $today, 100, 'Hari Ini', 'bg-green-500'],
            [$today->copy()->subYears(3), $today->copy()->subDay(), 100, 'Terlambat', 'bg-red-500'],
            [$today->copy()->subDays(1), $today->copy()->addDays(2), 33, 'Menunggu', 'bg-blue-500'],
            [$today->copy()->subDays(2), $today->copy()->addDays(1), 67, 'Menunggu', 'bg-blue-500'],
        ];
        $employees = [];
        foreach ($cases as $index => [$oldTmt, $newTmt]) {
            $employees[] = $this->reportEmployee([
                'nip' => sprintf('1985010120100110%02d', 40 + $index),
                'tmt_kgb_lama' => $oldTmt->toDateString(),
                'tmt_kgb_baru' => $newTmt->toDateString(),
            ]);
        }
        $user = new User(['role' => 'admin']);
        $this->actingAs($user);
        $this->repository->shouldReceive('getEmployeeByNip')
            ->times(count($employees))
            ->andReturn(...$employees);

        foreach ($cases as $index => [$oldTmt, $newTmt, $expectedProgress, $expectedStatus, $expectedBadge]) {
            $result = $this->service->getEmployee($employees[$index]->nip);

            $daysUntil = (int) $today->diffInDays($newTmt, false);
            $this->assertSame($expectedProgress, $result->progress_kgb);
            $this->assertSame($expectedStatus, $result->status_kgb);
            $this->assertSame($expectedBadge, $result->badge_kgb);
            $this->assertSame($daysUntil, $result->hari);
            $this->assertSame($newTmt->toDateString(), $result->tanggal_kgb->toDateString());
            $this->assertSame(4, $result->tahun_masa_kerja_lama);
            $this->assertSame(0, $result->bulan_masa_kerja_lama);
            $this->assertSame(6, $result->tahun_masa_kerja_baru);
            $this->assertSame(0, $result->bulan_masa_kerja_baru);
            if ($daysUntil <= 0) {
                $this->assertSame(0, $result->tahun_sisa);
                $this->assertSame(0, $result->bulan_sisa);
                $this->assertSame(0, $result->hari_sisa);
            } else {
                $remaining = $today->diff($newTmt);
                $this->assertSame($remaining->y, $result->tahun_sisa);
                $this->assertSame($remaining->m, $result->bulan_sisa);
                $this->assertSame($remaining->d, $result->hari_sisa);
            }
        }
    }

    public function test_prepare_kgb_information_uses_history_rank_and_salary_before_database_fallbacks(): void
    {
        $this->travelTo(Carbon::parse('2025-05-01'));
        $employeeRank = Rank::create([
            'kode' => 'IV/a',
            'nama' => 'Pangkat Pegawai Uji',
            'urutan_pangkat' => 13,
        ]);
        $historyRank = Rank::create([
            'kode' => 'IV/b',
            'nama' => 'Pangkat Riwayat Uji',
            'urutan_pangkat' => 14,
        ]);
        $rankWithoutOldSalary = Rank::create([
            'kode' => 'V/a',
            'nama' => 'Pangkat Tanpa Gaji Lama',
            'urutan_pangkat' => 15,
        ]);
        Salary::create([
            'id_pangkat' => $employeeRank->id,
            'tahun_masa_kerja' => 2,
            'gaji_pokok' => 2_500_000,
        ]);
        Salary::create([
            'id_pangkat' => $employeeRank->id,
            'tahun_masa_kerja' => 4,
            'gaji_pokok' => 2_900_000,
        ]);
        Salary::create([
            'id_pangkat' => $historyRank->id,
            'tahun_masa_kerja' => 2,
            'gaji_pokok' => 3_000_000,
        ]);
        Salary::create([
            'id_pangkat' => $historyRank->id,
            'tahun_masa_kerja' => 5,
            'gaji_pokok' => 3_400_000,
        ]);
        Salary::create([
            'id_pangkat' => $historyRank->id,
            'tahun_masa_kerja' => 7,
            'gaji_pokok' => 3_600_000,
        ]);
        Salary::create([
            'id_pangkat' => $rankWithoutOldSalary->id,
            'tahun_masa_kerja' => 5,
            'gaji_pokok' => 4_100_000,
        ]);
        $employee = $this->reportEmployee([
            'nip' => '198501012010011051',
            'id_pangkat' => $employeeRank->id,
            'tahun_masa_kerja' => 3,
            'tmt_kgb_lama' => '2023-05-01',
            'tmt_kgb_baru' => '2025-04-30',
        ]);
        $user = new User(['role' => 'admin']);
        $historyWithFallback = new KgbHistory([
            'id_pangkat' => $historyRank->id,
            'tmt_kgb_lama' => '2024-05-01',
            'tmt_kgb_baru' => '2026-05-01',
            'tahun_masa_kerja_lama' => 2,
            'bulan_masa_kerja_lama' => 2,
            'tahun_masa_kerja_baru' => 7,
            'bulan_masa_kerja_baru' => 2,
            'gaji_pokok_lama' => null,
            'gaji_pokok_baru' => null,
        ]);
        $historyWithStoredSalaries = new KgbHistory([
            'id_pangkat' => $historyRank->id,
            'tmt_kgb_lama' => '2024-05-01',
            'tmt_kgb_baru' => '2026-05-01',
            'tahun_masa_kerja_lama' => 3,
            'bulan_masa_kerja_lama' => 2,
            'tahun_masa_kerja_baru' => 6,
            'bulan_masa_kerja_baru' => 2,
            'gaji_pokok_lama' => 3_100_000,
            'gaji_pokok_baru' => 3_500_000,
        ]);
        $employeeWithoutOldSalary = $this->reportEmployee([
            'nip' => '198501012010011052',
            'id_pangkat' => $rankWithoutOldSalary->id,
            'tmt_kgb_lama' => '2023-05-01',
            'tmt_kgb_baru' => '2025-04-30',
        ]);
        $historyWithoutOldSalary = new KgbHistory([
            'id_pangkat' => $rankWithoutOldSalary->id,
            'tmt_kgb_lama' => '2024-05-01',
            'tmt_kgb_baru' => '2026-05-01',
            'tahun_masa_kerja_lama' => 3,
            'tahun_masa_kerja_baru' => 5,
            'gaji_pokok_lama' => null,
            'gaji_pokok_baru' => null,
        ]);
        $historyWithNullNewMonth = new KgbHistory([
            'id_pangkat' => $historyRank->id,
            'tmt_kgb_lama' => '2024-05-01',
            'tmt_kgb_baru' => '2026-05-01',
            'tahun_masa_kerja_lama' => 3,
            'bulan_masa_kerja_lama' => 2,
            'tahun_masa_kerja_baru' => 5,
            'bulan_masa_kerja_baru' => null,
            'gaji_pokok_lama' => 3_100_000,
            'gaji_pokok_baru' => 3_500_000,
        ]);
        $historyWithFallback->setRelation('employee', $employee);
        $historyWithStoredSalaries->setRelation('employee', $employee);
        $historyWithoutOldSalary->setRelation('employee', $employeeWithoutOldSalary);
        $historyWithNullNewMonth->setRelation('employee', $employee);
        $this->actingAs($user);
        $this->repository->shouldReceive('getHistoryById')
            ->times(4)
            ->andReturn(
                $historyWithFallback,
                $historyWithStoredSalaries,
                $historyWithoutOldSalary,
                $historyWithNullNewMonth
            );

        $fallbackResult = $this->service->getHistoryById(51)->employee;
        $this->assertSame('2026-05-01', $fallbackResult->tanggal_kgb->toDateString());
        $this->assertSame(365, $fallbackResult->hari);
        $this->assertSame('Menunggu', $fallbackResult->status_kgb);
        $this->assertSame('bg-blue-500', $fallbackResult->badge_kgb);
        $this->assertSame(50, $fallbackResult->progress_kgb);
        $this->assertSame(1, $fallbackResult->tahun_sisa);
        $this->assertSame(0, $fallbackResult->bulan_sisa);
        $this->assertSame(0, $fallbackResult->hari_sisa);
        $this->assertSame(2, $fallbackResult->tahun_masa_kerja_lama);
        $this->assertSame(2, $fallbackResult->bulan_masa_kerja_lama);
        $this->assertSame(7, $fallbackResult->tahun_masa_kerja_baru);
        $this->assertSame(2, $fallbackResult->bulan_masa_kerja_baru);
        $this->assertSame(3_000_000, $fallbackResult->gaji_pokok_lama->gaji_pokok);
        $this->assertSame(3_600_000, $fallbackResult->gaji_pokok_baru->gaji_pokok);

        $storedResult = $this->service->getHistoryById(52)->employee;
        $this->assertSame(3, $storedResult->tahun_masa_kerja_lama);
        $this->assertSame(2, $storedResult->bulan_masa_kerja_lama);
        $this->assertSame(6, $storedResult->tahun_masa_kerja_baru);
        $this->assertSame(2, $storedResult->bulan_masa_kerja_baru);
        $this->assertSame(3_100_000, $storedResult->gaji_pokok_lama->gaji_pokok);
        $this->assertSame(3_500_000, $storedResult->gaji_pokok_baru->gaji_pokok);

        $fallbackSalaryResult = $this->service->getHistoryById(53)->employee;
        $this->assertSame(4_100_000, $fallbackSalaryResult->gaji_pokok_lama->gaji_pokok);
        $this->assertSame(4_100_000, $fallbackSalaryResult->gaji_pokok_baru->gaji_pokok);

        $nullMonthResult = $this->service->getHistoryById(54)->employee;
        $this->assertSame(0, $nullMonthResult->bulan_masa_kerja_baru);
    }

    public function test_prepare_surat_data_prefers_current_old_sk_and_previous_official(): void
    {
        $user = new User(['role' => 'admin']);
        $employee = $this->reportEmployee([
            'nip' => '198501012010011061',
            'id_pangkat' => null,
        ]);
        $current = new KgbHistory([
            'nomor_sk_lama' => '800/CURRENT-OLD/2024',
            'tanggal_sk_lama' => '2024-10-15',
            'nomor_sk_baru' => '800/CURRENT-NEW/2026',
            'tanggal_sk_baru' => '2026-10-15',
            'tmt_kgb_lama' => '2024-10-15',
            'tmt_kgb_baru' => '2026-10-15',
            'tahun_masa_kerja_lama' => 4,
            'bulan_masa_kerja_lama' => 3,
            'tahun_masa_kerja_baru' => 6,
            'bulan_masa_kerja_baru' => 3,
            'gaji_pokok_lama' => 2_800_000,
            'gaji_pokok_baru' => 3_100_000,
            'dasar_hukum_kgb' => 'Dasar hukum tersimpan',
        ]);
        $current->id = 61;
        $previous = new KgbHistory([
            'nomor_sk_baru' => '800/PREVIOUS/2024',
            'tanggal_sk_baru' => '2023-10-15',
            'pejabat' => 'Pejabat SK Sebelumnya',
        ]);
        $this->actingAs($user);
        $this->repository->shouldReceive('getCamat')
            ->once()
            ->andReturnNull();
        $this->repository->shouldReceive('getEmployeeByNip')
            ->once()
            ->with($employee->nip, $user)
            ->andReturn($employee);
        $this->repository->shouldReceive('getPreviousHistory')
            ->once()
            ->with($employee->nip, $user, '2026-10-15', 61)
            ->andReturn($previous);

        $surat = $this->service->prepareSuratData($employee, $current);

        $this->assertSame('800/CURRENT-OLD/2024', $surat->nomor_sk_lama);
        $this->assertSame('2024-10-15', $surat->tanggal_sk_lama->toDateString());
        $this->assertSame('2026-10-15', $surat->tanggal_sk_baru->toDateString());
        $this->assertSame('Pejabat SK Sebelumnya', $surat->old_official);
        $this->assertSame('800/CURRENT-NEW/2026', $surat->nomor_sk_baru);
        $this->assertSame('2024-10-15', $surat->tmt_kgb_lama->toDateString());
        $this->assertSame('2026-10-15', $surat->tmt_kgb_baru->toDateString());
        $this->assertSame(4, $surat->tahun_masa_kerja_lama);
        $this->assertSame(3, $surat->bulan_masa_kerja_lama);
        $this->assertSame(6, $surat->tahun_masa_kerja_baru);
        $this->assertSame(3, $surat->bulan_masa_kerja_baru);
        $this->assertSame(2_800_000, $surat->gaji_pokok_lama);
        $this->assertSame(3_100_000, $surat->gaji_pokok_baru);
        $this->assertSame('Dasar hukum tersimpan', $surat->dasar_hukum_kgb);
    }

    public function test_prepare_surat_data_uses_stored_official_and_falls_back_when_camat_position_is_missing(): void
    {
        $employee = $this->reportEmployee(['id_pangkat' => null]);
        $storedOfficial = new KgbHistory([
            'pejabat' => 'Pejabat Riwayat',
            'nomor_sk_baru' => '800/HISTORY/2026',
        ]);
        $missingOfficial = new KgbHistory();
        $previousWithoutOfficial = new KgbHistory(['pejabat' => null]);
        $camatWithoutPosition = new Employee();
        $user = new User(['role' => 'admin']);
        $this->actingAs($user);
        $this->repository->shouldReceive('getCamat')
            ->times(3)
            ->andReturn($camatWithoutPosition, $camatWithoutPosition, $camatWithoutPosition);
        $this->repository->shouldReceive('getEmployeeByNip')
            ->once()
            ->with($employee->nip, $user)
            ->andReturn($employee);
        $this->repository->shouldReceive('getPreviousHistory')
            ->once()
            ->andReturn($previousWithoutOfficial);

        $stored = $this->service->prepareSuratData($employee, $storedOfficial, true);
        $missingHistoryOfficial = $this->service->prepareSuratData($employee, $missingOfficial, true);
        $missingPreviousOfficial = $this->service->prepareSuratData($employee);

        $this->assertSame('Pejabat Riwayat', $stored->old_official);
        $this->assertSame('Camat Pekalongan Timur', $missingHistoryOfficial->old_official);
        $this->assertSame('Camat Pekalongan Timur', $missingPreviousOfficial->old_official);
    }

    public function test_prepare_surat_data_falls_back_to_previous_sk_and_camat(): void
    {
        $user = new User(['role' => 'admin']);
        $employee = $this->reportEmployee([
            'nip' => '198501012010011062',
            'id_pangkat' => null,
        ]);
        $current = new KgbHistory([
            'tmt_kgb_lama' => '2024-10-15',
            'tmt_kgb_baru' => '2026-10-15',
            'nomor_sk_lama' => null,
            'tanggal_sk_lama' => null,
        ]);
        $previous = new KgbHistory([
            'nomor_sk_baru' => '800/PREVIOUS/2024',
            'tanggal_sk_baru' => '2024-10-15',
            'pejabat' => null,
        ]);
        $camat = new Employee();
        $camat->setRelation('position', new Position(['nama' => 'Camat Uji']));
        $this->actingAs($user);
        $this->repository->shouldReceive('getCamat')
            ->once()
            ->andReturn($camat);
        $this->repository->shouldReceive('getEmployeeByNip')
            ->once()
            ->with($employee->nip, $user)
            ->andReturn($employee);
        $this->repository->shouldReceive('getPreviousHistory')
            ->once()
            ->with($employee->nip, $user, '2026-10-15', null)
            ->andReturn($previous);

        $surat = $this->service->prepareSuratData($employee, $current);

        $this->assertSame('800/PREVIOUS/2024', $surat->nomor_sk_lama);
        $this->assertSame('2024-10-15', $surat->tanggal_sk_lama->toDateString());
        $this->assertSame('Camat Uji', $surat->old_official);
        $this->assertSame('2026-10-15', $surat->tmt_kgb_baru->toDateString());
        $this->assertSame('800/PREVIOUS/2024', $surat->nomor_sk_sebelumnya);
    }

    public function test_prepare_surat_data_uses_defaults_when_history_is_missing(): void
    {
        $this->travelTo(Carbon::parse('2026-11-15'));
        $employee = $this->reportEmployee([
            'nip' => '198501012010011063',
            'id_pangkat' => null,
        ]);
        $this->repository->shouldReceive('getCamat')
            ->once()
            ->andReturnNull();

        $warnings = [];
        set_error_handler(static function (int $severity, string $message) use (&$warnings): bool {
            $warnings[] = [$severity, $message];

            return true;
        });
        try {
            $surat = $this->service->prepareSuratData($employee, null, true);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $warnings);
        $this->assertSame('-', $surat->nomor_sk_lama);
        $this->assertNull($surat->tanggal_sk_lama);
        $this->assertSame('Camat Pekalongan Timur', $surat->old_official);
        $this->assertSame('B/ - /2026', $surat->nomor_sk_baru);
        $this->assertNull($surat->tanggal_sk_sebelumnya);
        $this->assertSame('2026-12-15', $surat->tmt_kgb_baru->toDateString());
        $this->assertSame(4, $surat->tahun_masa_kerja_lama);
        $this->assertSame(0, $surat->bulan_masa_kerja_lama);
        $this->assertSame(6, $surat->tahun_masa_kerja_baru);
        $this->assertSame(0, $surat->bulan_masa_kerja_baru);
        $this->assertSame(0, $surat->gaji_pokok_lama);
        $this->assertSame(0, $surat->gaji_pokok_baru);
        $this->assertSame(
            'Pasal 1 Peraturan Pemerintah Republik Indonesia Nomor : 5 Tahun 2024',
            $surat->dasar_hukum_kgb
        );
    }

    public function test_prepare_surat_data_handles_missing_history_without_warnings(): void
    {
        $this->travelTo(Carbon::parse('2026-11-15'));
        $user = new User(['role' => 'admin']);
        $employee = $this->reportEmployee([
            'nip' => '198501012010011064',
            'id_pangkat' => null,
            'tmt_kgb_lama' => '2024-11-15',
            'tmt_kgb_baru' => '2026-12-15',
        ]);
        $this->actingAs($user);
        $this->repository->shouldReceive('getCamat')
            ->once()
            ->andReturnNull();
        $this->repository->shouldReceive('getEmployeeByNip')
            ->once()
            ->with($employee->nip, $user)
            ->andReturn($employee);
        $this->repository->shouldReceive('getPreviousHistory')
            ->once()
            ->with($employee->nip, $user, '2026-12-15', null)
            ->andReturnNull();

        $warnings = [];
        set_error_handler(static function (int $severity, string $message) use (&$warnings): bool {
            $warnings[] = [$severity, $message];

            return true;
        });
        try {
            $surat = $this->service->prepareSuratData($employee);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $warnings);
        $this->assertSame('-', $surat->nomor_sk_lama);
        $this->assertNull($surat->tanggal_sk_lama);
        $this->assertSame('Camat Pekalongan Timur', $surat->old_official);
        $this->assertSame('B/ - /2026', $surat->nomor_sk_baru);
        $this->assertSame('2026-11-15', $surat->tanggal_sk_baru->toDateString());
        $this->assertSame('2024-11-15', $surat->tmt_kgb_lama->toDateString());
        $this->assertSame('2026-12-15', $surat->tmt_kgb_baru->toDateString());
        $this->assertSame(
            'Pasal 1 Peraturan Pemerintah Republik Indonesia Nomor : 5 Tahun 2024',
            $surat->dasar_hukum_kgb
        );
    }

    public function test_print_kgb_persists_history_updates_employee_and_notifies_after_commit(): void
    {
        $this->travelTo(Carbon::parse('2026-11-15'));
        $fixture = $this->createPrintFixture();
        $employee = $fixture['employee'];
        $user = $fixture['user'];
        $repository = Mockery::mock(ReportRepository::class);
        $repository->shouldReceive('getEmployeeByNip')
            ->once()
            ->with($employee->nip, $user)
            ->andReturn($employee);
        $repository->shouldReceive('getExistingKgbHistory')
            ->once()
            ->andReturnNull();
        $this->actingAs($user);
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('notifyKgbProcessed')
            ->once()
            ->withArgs(fn (Employee $fresh, int $salary): bool =>
                $fresh->nip === $employee->nip
                && $fresh->tmt_kgb_lama->toDateString()
                    === $employee->tmt_kgb_baru->toDateString()
                && $salary === 3_100_000
            );
        $this->app->instance(NotificationService::class, $notificationService);

        $service = new ReportService($repository);
        $history = $service->printKgb($employee->nip, [
            'nomor_sk_lama' => '  800/OLD/2024  ',
            'nomor_sk_baru' => '  800/NEW/2026  ',
            'tanggal_sk_lama' => '  1 Januari 2025  ',
            'tanggal_sk_baru' => '  15 November 2026  ',
            'pejabat' => '  Camat Uji  ',
            'dasar_hukum_kgb' => '  Dasar hukum uji  ',
        ]);

        $this->assertInstanceOf(KgbHistory::class, $history);
        $this->assertSame('  800/OLD/2024  ', $history->nomor_sk_lama);
        $this->assertSame('800/NEW/2026', $history->nomor_sk_baru);
        $this->assertSame('2025-01-01', $history->tanggal_sk_lama->toDateString());
        $this->assertSame('2026-11-15', $history->tanggal_sk_baru->toDateString());
        $this->assertSame('Camat Uji', $history->pejabat);
        $this->assertSame('Dasar hukum uji', $history->dasar_hukum_kgb);
        $this->assertSame('2024-11-15', $history->tmt_kgb_lama->toDateString());
        $this->assertSame('2026-12-15', $history->tmt_kgb_baru->toDateString());
        $this->assertSame(4, $history->tahun_masa_kerja_lama);
        $this->assertSame(3, $history->bulan_masa_kerja_lama);
        $this->assertSame(6, $history->tahun_masa_kerja_baru);
        $this->assertSame(3, $history->bulan_masa_kerja_baru);
        $this->assertSame(2_800_000, $history->gaji_pokok_lama);
        $this->assertSame(3_100_000, $history->gaji_pokok_baru);
        $this->assertSame($employee->id_pangkat, $history->id_pangkat);
        $this->assertSame($user->id, $history->id_user);
        $this->assertTrue($history->relationLoaded('employee'));
        $this->assertSame($employee->nip, $history->employee->nip);
        $this->assertDatabaseHas('kgb_histories', [
            'nip' => $employee->nip,
            'tmt_kgb_baru' => $employee->tmt_kgb_baru->toDateTimeString(),
            'gaji_pokok_baru' => 3_100_000,
        ]);

        $employee->refresh();
        $this->assertSame(6, $employee->tahun_masa_kerja);
        $this->assertSame(3, $employee->bulan_masa_kerja);
        $this->assertSame('2026-12-15', $employee->tmt_kgb_lama->toDateString());
        $this->assertSame('2028-12-15', $employee->tmt_kgb_baru->toDateString());
    }

    public function test_print_kgb_parses_alternative_date_format(): void
    {
        $this->travelTo(Carbon::parse('2026-11-15'));
        $fixture = $this->createPrintFixture();
        $employee = $fixture['employee'];
        $user = $fixture['user'];
        $repository = Mockery::mock(ReportRepository::class);
        $repository->shouldReceive('getEmployeeByNip')
            ->once()
            ->with($employee->nip, $user)
            ->andReturn($employee);
        $repository->shouldReceive('getExistingKgbHistory')
            ->once()
            ->andReturnNull();
        $this->actingAs($user);
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('notifyKgbProcessed')->once();
        $this->app->instance(NotificationService::class, $notificationService);

        $previousLocale = Carbon::getLocale();
        Carbon::setLocale('en');
        try {
            $history = (new ReportService($repository))->printKgb($employee->nip, [
                'tanggal_sk_baru' => '  15 Februari 2026  ',
            ]);

            $this->assertNull($history->tanggal_sk_lama);
            $this->assertSame('2026-02-15', $history->tanggal_sk_baru->toDateString());
            $this->assertSame('id', Carbon::getLocale());
        } finally {
            Carbon::setLocale($previousLocale);
        }
    }

    public function test_print_kgb_parses_old_date_fallback_and_sets_indonesian_locale(): void
    {
        $this->travelTo(Carbon::parse('2026-11-15'));
        $fixture = $this->createPrintFixture();
        $employee = $fixture['employee'];
        $user = $fixture['user'];
        $repository = Mockery::mock(ReportRepository::class);
        $repository->shouldReceive('getEmployeeByNip')
            ->once()
            ->with($employee->nip, $user)
            ->andReturn($employee);
        $repository->shouldReceive('getExistingKgbHistory')
            ->once()
            ->andReturnNull();
        $this->actingAs($user);
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('notifyKgbProcessed')->once();
        $this->app->instance(NotificationService::class, $notificationService);

        $previousLocale = Carbon::getLocale();
        Carbon::setLocale('en');
        try {
            $history = (new ReportService($repository))->printKgb($employee->nip, [
                'tanggal_sk_lama' => '2025-01-02',
            ]);

            $this->assertSame('2025-01-02', $history->tanggal_sk_lama->toDateString());
            $this->assertSame('2026-11-15', $history->tanggal_sk_baru->toDateString());
            $this->assertSame('id', Carbon::getLocale());
        } finally {
            Carbon::setLocale($previousLocale);
        }
    }

    public function test_print_kgb_uses_zero_salary_when_salary_records_are_missing(): void
    {
        $this->travelTo(Carbon::parse('2026-11-15'));
        $fixture = $this->createPrintFixture();
        $employee = $fixture['employee'];
        $user = $fixture['user'];
        $employee->tahun_masa_kerja = null;
        $employee->bulan_masa_kerja = null;
        $employee->save();
        Salary::where('id_pangkat', $employee->id_pangkat)->delete();
        $repository = Mockery::mock(ReportRepository::class);
        $repository->shouldReceive('getEmployeeByNip')
            ->once()
            ->with($employee->nip, $user)
            ->andReturn($employee);
        $repository->shouldReceive('getExistingKgbHistory')
            ->once()
            ->andReturnNull();
        $this->actingAs($user);
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('notifyKgbProcessed')
            ->once()
            ->withArgs(fn (Employee $fresh, int $salary): bool =>
                $fresh->nip === $employee->nip && $salary === 0
            );
        $this->app->instance(NotificationService::class, $notificationService);

        $history = (new ReportService($repository))->printKgb($employee->nip);

        $this->assertSame(0, $history->gaji_pokok_lama);
        $this->assertSame(0, $history->gaji_pokok_baru);
        $this->assertSame(0, $history->tahun_masa_kerja_lama);
        $this->assertSame(0, $history->bulan_masa_kerja_lama);
        $this->assertSame(2, $history->tahun_masa_kerja_baru);
        $this->assertSame(0, $history->bulan_masa_kerja_baru);
    }

    public function test_print_kgb_uses_current_date_when_sk_dates_are_omitted(): void
    {
        $this->travelTo(Carbon::parse('2026-11-15'));
        $fixture = $this->createPrintFixture();
        $employee = $fixture['employee'];
        $user = $fixture['user'];
        $repository = Mockery::mock(ReportRepository::class);
        $repository->shouldReceive('getEmployeeByNip')
            ->once()
            ->with($employee->nip, $user)
            ->andReturn($employee);
        $repository->shouldReceive('getExistingKgbHistory')
            ->once()
            ->andReturnNull();
        $this->actingAs($user);
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('notifyKgbProcessed')->once();
        $this->app->instance(NotificationService::class, $notificationService);

        $history = (new ReportService($repository))->printKgb($employee->nip);

        $this->assertNull($history->tanggal_sk_lama);
        $this->assertSame('2026-11-15', $history->tanggal_sk_baru->toDateString());
    }

    public function test_print_kgb_skips_notification_if_employee_disappears_after_commit(): void
    {
        $fixture = $this->createPrintFixture();
        $employee = $fixture['employee'];
        $user = $fixture['user'];
        $repository = Mockery::mock(ReportRepository::class);
        $repository->shouldReceive('getEmployeeByNip')
            ->once()
            ->with($employee->nip, $user)
            ->andReturn($employee);
        $repository->shouldReceive('getExistingKgbHistory')
            ->once()
            ->andReturnNull();
        $this->actingAs($user);
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldNotReceive('notifyKgbProcessed');
        $this->app->instance(NotificationService::class, $notificationService);
        DB::unprepared(
            "CREATE TRIGGER remove_report_employee_after_update AFTER UPDATE ON employees "
            . "BEGIN DELETE FROM employees WHERE nip = NEW.nip; END"
        );

        $history = (new ReportService($repository))->printKgb($employee->nip);

        $this->assertNull($history);
        $this->assertDatabaseMissing('employees', ['nip' => $employee->nip]);
        $this->assertDatabaseMissing('kgb_histories', ['nip' => $employee->nip]);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function test_print_kgb_rejects_a_non_model_result_from_history_creation(): void
    {
        $this->assertFalse(class_exists(KgbHistory::class, false));
        $query = Mockery::mock();
        $query->shouldReceive('where')->once()->andReturnSelf();
        $query->shouldReceive('whereDate')->once()->andReturnSelf();
        $query->shouldReceive('lockForUpdate')->once()->andReturnSelf();
        $query->shouldReceive('first')->once()->andReturnNull();
        $historyModel = Mockery::mock('alias:' . KgbHistory::class);
        $historyModel->shouldReceive('query')->once()->andReturn($query);
        $historyModel->shouldReceive('create')->once()->andReturn(new \stdClass());

        $fixture = $this->createPrintFixture();
        $employee = $fixture['employee'];
        $user = $fixture['user'];
        $repository = Mockery::mock(ReportRepository::class);
        $repository->shouldReceive('getEmployeeByNip')
            ->once()
            ->with($employee->nip, $user)
            ->andReturn($employee);
        $repository->shouldReceive('getExistingKgbHistory')
            ->once()
            ->andReturnNull();
        $this->actingAs($user);
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldNotReceive('notifyKgbProcessed');
        $this->app->instance(NotificationService::class, $notificationService);

        try {
            (new ReportService($repository))->printKgb($employee->nip);
            $this->fail('A non-model history result must be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Data riwayat KGB tidak ditemukan setelah proses penyimpanan.',
                $exception->getMessage()
            );
        }
    }

    public function test_print_kgb_rejects_duplicate_found_inside_transaction(): void
    {
        $fixture = $this->createPrintFixture();
        $employee = $fixture['employee'];
        $user = $fixture['user'];
        KgbHistory::create([
            'nip' => $employee->nip,
            'id_pangkat' => $employee->id_pangkat,
            'tmt_kgb_baru' => $employee->tmt_kgb_baru,
            'id_user' => $user->id,
        ]);
        $repository = Mockery::mock(ReportRepository::class);
        $repository->shouldReceive('getEmployeeByNip')
            ->once()
            ->with($employee->nip, $user)
            ->andReturn($employee);
        $repository->shouldReceive('getExistingKgbHistory')
            ->once()
            ->andReturnNull();
        $this->actingAs($user);
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldNotReceive('notifyKgbProcessed');
        $this->app->instance(NotificationService::class, $notificationService);
        $service = new ReportService($repository);

        try {
            $service->printKgb($employee->nip);
            $this->fail('A duplicate KGB period must be rejected in the transaction.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertDatabaseCount('kgb_histories', 1);
        $this->assertDatabaseHas('employees', [
            'nip' => $employee->nip,
            'tahun_masa_kerja' => 4,
        ]);
    }

    public function test_print_kgb_rolls_back_history_and_employee_update_when_transaction_fails(): void
    {
        $fixture = $this->createPrintFixture();
        $employee = $fixture['employee'];
        $user = $fixture['user'];
        $repository = Mockery::mock(ReportRepository::class);
        $repository->shouldReceive('getEmployeeByNip')
            ->once()
            ->with($employee->nip, $user)
            ->andReturn($employee);
        $repository->shouldReceive('getExistingKgbHistory')
            ->once()
            ->andReturnNull();
        $this->actingAs($user);
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldNotReceive('notifyKgbProcessed');
        $this->app->instance(NotificationService::class, $notificationService);
        DB::unprepared(
            "CREATE TRIGGER fail_report_employee_update BEFORE UPDATE ON employees "
            . "BEGIN SELECT RAISE(ABORT, 'forced test failure'); END"
        );

        try {
            (new ReportService($repository))->printKgb($employee->nip);
            $this->fail('The update trigger should abort the print transaction.');
        } catch (QueryException) {
            $this->assertDatabaseMissing('kgb_histories', [
                'nip' => $employee->nip,
            ]);
            $this->assertDatabaseHas('employees', [
                'nip' => $employee->nip,
                'tahun_masa_kerja' => 4,
                'tmt_kgb_lama' => $employee->tmt_kgb_lama->toDateTimeString(),
            ]);
        }
    }
}


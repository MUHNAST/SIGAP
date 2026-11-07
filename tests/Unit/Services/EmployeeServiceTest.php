<?php

namespace Tests\Unit\Services;

use App\Imports\EmployeeImport;
use App\Models\Department;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Pppk;
use App\Models\PromotionHistory;
use App\Models\Rank;
use App\Models\User;
use App\Repositories\EmployeeRepository;
use App\Services\EmployeeService;
use App\Services\EmployeeUserSyncService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EmployeeServiceTest extends TestCase
{
    use RefreshDatabase;

    private EmployeeService $service;
    private $employeeRepository;
    private $employeeImport;
    private $userSyncService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->employeeRepository = Mockery::mock(EmployeeRepository::class);
        $this->employeeImport = Mockery::mock(EmployeeImport::class);
        $this->userSyncService = Mockery::mock(EmployeeUserSyncService::class);

        $this->service = new EmployeeService(
            $this->employeeRepository,
            $this->employeeImport,
            $this->userSyncService
        );
    }

    /**
     * Memeriksa normalisasi nomor telepon.
     */
    public function test_normalize_phone_mengubah_format_62_dan_menghilangkan_karakter_non_digit(): void
    {
        $reflection = new \ReflectionClass(EmployeeService::class);
        $method = $reflection->getMethod('normalizePhone');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($this->service, null));
        $this->assertNull($method->invoke($this->service, ''));

        // 628123456789 -> 08123456789
        $this->assertSame('08123456789', $method->invoke($this->service, '628123456789'));

        // Menghilangkan spasi dan tanda minus
        $this->assertSame('08123456789', $method->invoke($this->service, '+62 812-3456-789'));

        // Menambahkan prefix 0 jika tidak ada
        $this->assertSame('08123456789', $method->invoke($this->service, '8123456789'));
    }

    /**
     * Memeriksa kalkulasi TMT Pangkat Baru dan KGB Baru untuk pegawai PNS.
     */
    public function test_prepare_employee_data_pns_menghitung_tmt_pangkat_dan_kgb_baru(): void
    {
        Pppk::create([
            'kode' => '0',
            'nama' => 'Bukan PPPK',
            'urutan_golongan' => 0,
        ]);

        $reflection = new \ReflectionClass(EmployeeService::class);
        $method = $reflection->getMethod('prepareEmployeeData');
        $method->setAccessible(true);

        $input = [
            'status_pegawai' => 'PNS',
            'tmt_pangkat_lama' => '2020-04-01',
            'tmt_kgb_lama' => '2022-01-01',
            'no_hp' => '6281234567890',
        ];

        $output = $method->invoke($this->service, $input, false);

        // PNS otomatis mendapat kode_pppk 0
        $this->assertSame('0', $output['kode_pppk']);
        // TMT pangkat bertambah 4 tahun
        $this->assertSame('2024-04-01', $output['tmt_pangkat_baru']);
        // TMT KGB bertambah 2 tahun
        $this->assertSame('2024-01-01', $output['tmt_kgb_baru']);
        // Nomor HP dinormalisasi
        $this->assertSame('081234567890', $output['no_hp']);
    }

    /**
     * Memeriksa pegawai PNS dengan TMT pangkat lama kosong menghasilkan TMT baru null.
     */
    public function test_prepare_employee_data_pns_tanpa_tmt_lama_menghasilkan_null(): void
    {
        Pppk::create([
            'kode' => '0',
            'nama' => 'Bukan PPPK',
            'urutan_golongan' => 0,
        ]);

        $reflection = new \ReflectionClass(EmployeeService::class);
        $method = $reflection->getMethod('prepareEmployeeData');
        $method->setAccessible(true);

        $input = [
            'status_pegawai' => 'PNS',
            'tmt_pangkat_lama' => null,
            'tmt_kgb_lama' => null,
        ];

        $output = $method->invoke($this->service, $input, false);

        $this->assertNull($output['tmt_pangkat_baru']);
        $this->assertNull($output['tmt_kgb_baru']);
    }

    /**
     * Memeriksa pegawai status PPPK tidak memiliki jadwal kenaikan pangkat PNS.
     */
    public function test_prepare_employee_data_pppk_menghapus_tmt_pangkat(): void
    {
        $defaultRank = Rank::create([
            'kode' => '0',
            'nama' => 'Bukan PNS',
            'urutan_pangkat' => 0,
        ]);

        $reflection = new \ReflectionClass(EmployeeService::class);
        $method = $reflection->getMethod('prepareEmployeeData');
        $method->setAccessible(true);

        $input = [
            'status_pegawai' => 'PPPK',
            'tmt_pangkat_lama' => '2020-01-01',
            'tmt_pangkat_baru' => '2024-01-01',
            'tmt_kgb_lama' => '2022-01-01',
        ];

        $output = $method->invoke($this->service, $input, false);

        $this->assertSame($defaultRank->id, $output['id_pangkat']);
        $this->assertNull($output['tmt_pangkat_lama']);
        $this->assertNull($output['tmt_pangkat_baru']);
        $this->assertSame('2024-01-01', $output['tmt_kgb_baru']);
    }

    /**
     * Memeriksa perhitungan masa pensiun berdasarkan BUP jabatan.
     */
    public function test_calculate_retirement_data_menghitung_tanggal_pensiun_dan_tmt_pensiun_ke_awal_bulan_berikutnya(): void
    {
        Position::create([
            'kode' => 'JAB-01',
            'nama' => 'Staf Pelaksana',
            'usia_pensiun' => 58,
        ]);

        $reflection = new \ReflectionClass(EmployeeService::class);
        $method = $reflection->getMethod('calculateRetirementData');
        $method->setAccessible(true);

        $input = [
            'tanggal_lahir' => '1968-08-15',
            'kode_jabatan' => 'JAB-01',
        ];

        $output = $method->invoke($this->service, $input);

        // 1968-08-15 + 58 tahun = 2026-08-15
        $this->assertSame('2026-08-15', $output['tanggal_pensiun']);
        // TMT pensiun efektif tanggal 1 bulan berikutnya: 2026-09-01
        $this->assertSame('2026-09-01', $output['tmt_pensiun']);
    }

    /**
     * Memeriksa kalkulasi masa pensiun mengembalikan array asli jika parameter tidak lengkap.
     */
    public function test_calculate_retirement_data_mengabaikan_jika_data_tidak_lengkap(): void
    {
        $reflection = new \ReflectionClass(EmployeeService::class);
        $method = $reflection->getMethod('calculateRetirementData');
        $method->setAccessible(true);

        $emptyLahir = ['kode_jabatan' => 'JAB-01'];
        $this->assertSame($emptyLahir, $method->invoke($this->service, $emptyLahir));

        $emptyJabatan = ['tanggal_lahir' => '1970-01-01'];
        $this->assertSame($emptyJabatan, $method->invoke($this->service, $emptyJabatan));

        $jabatanTidakAda = [
            'tanggal_lahir' => '1970-01-01',
            'kode_jabatan' => 'NON-EXISTENT',
        ];
        $this->assertSame($jabatanTidakAda, $method->invoke($this->service, $jabatanTidakAda));
    }

    public function test_calculate_retirement_data_uses_the_retirement_age_of_each_position(): void
    {
        Position::create([
            'kode' => 'JAB-58',
            'nama' => 'Jabatan BUP 58',
            'usia_pensiun' => 58,
        ]);
        Position::create([
            'kode' => 'JAB-65',
            'nama' => 'Jabatan BUP 65',
            'usia_pensiun' => 65,
        ]);

        $reflection = new \ReflectionClass(EmployeeService::class);
        $method = $reflection->getMethod('calculateRetirementData');
        $method->setAccessible(true);

        $standardAge = $method->invoke($this->service, [
            'tanggal_lahir' => '1970-04-15',
            'kode_jabatan' => 'JAB-58',
        ]);
        $extendedAge = $method->invoke($this->service, [
            'tanggal_lahir' => '1970-04-15',
            'kode_jabatan' => 'JAB-65',
        ]);

        $this->assertSame('2028-04-15', $standardAge['tanggal_pensiun']);
        $this->assertSame('2028-05-01', $standardAge['tmt_pensiun']);
        $this->assertSame('2035-04-15', $extendedAge['tanggal_pensiun']);
        $this->assertSame('2035-05-01', $extendedAge['tmt_pensiun']);
    }

    /**
     * Memeriksa perhitungan selisih masa kerja dari tanggal masuk kerja.
     */
    public function test_calculate_service_period_menghitung_tahun_dan_bulan(): void
    {
        $reflection = new \ReflectionClass(EmployeeService::class);
        $method = $reflection->getMethod('calculateServicePeriod');
        $method->setAccessible(true);

        // Kasus kosong
        $resultKosong = $method->invoke($this->service, []);
        $this->assertSame(0, $resultKosong['tahun_masa_kerja']);
        $this->assertSame(0, $resultKosong['bulan_masa_kerja']);

        // Kasus tanggal di masa depan
        $future = Carbon::today()->addYear()->format('Y-m-d');
        $resultFuture = $method->invoke($this->service, ['tanggal_masuk' => $future]);
        $this->assertSame(0, $resultFuture['tahun_masa_kerja']);
        $this->assertSame(0, $resultFuture['bulan_masa_kerja']);

        // Kasus tanggal di masa lalu (misal 3 tahun 4 bulan yang lalu)
        $past = Carbon::today()->subYears(3)->subMonths(4)->format('Y-m-d');
        $resultPast = $method->invoke($this->service, ['tanggal_masuk' => $past]);
        $this->assertSame(3, $resultPast['tahun_masa_kerja']);
        $this->assertSame(4, $resultPast['bulan_masa_kerja']);
    }

    /**
     * Memeriksa penyesuaian masa kerja pada kenaikan golongan PNS (I -> II, II -> III, III -> IV).
     */
    public function test_adjust_service_period_after_promotion_memotong_masa_kerja_sesuai_aturan_golongan(): void
    {
        $reflection = new \ReflectionClass(EmployeeService::class);
        $method = $reflection->getMethod('adjustServicePeriodAfterPromotion');
        $method->setAccessible(true);

        $rankI_d = Rank::create(['kode' => 'I/d', 'nama' => 'Juru Tingkat I', 'urutan_pangkat' => 4]);
        $rankII_a = Rank::create(['kode' => 'II/a', 'nama' => 'Pengatur Muda', 'urutan_pangkat' => 5]);
        $rankII_d = Rank::create(['kode' => 'II/d', 'nama' => 'Pengatur Tingkat I', 'urutan_pangkat' => 8]);
        $rankIII_a = Rank::create(['kode' => 'III/a', 'nama' => 'Penata Muda', 'urutan_pangkat' => 9]);
        $rankIII_d = Rank::create(['kode' => 'III/d', 'nama' => 'Penata Tingkat I', 'urutan_pangkat' => 12]);
        $rankIV_a = Rank::create(['kode' => 'IV/a', 'nama' => 'Pembina', 'urutan_pangkat' => 13]);

        // 1. Golongan I -> II: dikurangi 6 tahun (72 bulan)
        $emp1 = new Employee();
        $emp1->tahun_masa_kerja = 8;
        $emp1->bulan_masa_kerja = 0;
        $method->invoke($this->service, $emp1, $rankI_d, $rankII_a);
        $this->assertSame(2, $emp1->tahun_masa_kerja); // 8 tahun - 6 tahun = 2 tahun
        $this->assertSame(0, $emp1->bulan_masa_kerja);

        // 2. Golongan II -> III: dikurangi 5 tahun (60 bulan)
        $emp2 = new Employee();
        $emp2->tahun_masa_kerja = 7;
        $emp2->bulan_masa_kerja = 6;
        $method->invoke($this->service, $emp2, $rankII_d, $rankIII_a);
        $this->assertSame(2, $emp2->tahun_masa_kerja); // 7 tahun - 5 tahun = 2 tahun
        $this->assertSame(6, $emp2->bulan_masa_kerja);

        // 3. Golongan III -> IV: dikurangi 1 bulan
        $emp3 = new Employee();
        $emp3->tahun_masa_kerja = 10;
        $emp3->bulan_masa_kerja = 2;
        $method->invoke($this->service, $emp3, $rankIII_d, $rankIV_a);
        $this->assertSame(10, $emp3->tahun_masa_kerja);
        $this->assertSame(1, $emp3->bulan_masa_kerja); // 2 bulan - 1 bulan = 1 bulan

        // 4. Kenaikan dalam kelompok yang sama (misal III/a ke III/b): tidak ada pemotongan
        $rankIII_b = Rank::create(['kode' => 'III/b', 'nama' => 'Penata Muda Tingkat I', 'urutan_pangkat' => 10]);
        $emp4 = new Employee();
        $emp4->tahun_masa_kerja = 4;
        $emp4->bulan_masa_kerja = 0;
        $method->invoke($this->service, $emp4, $rankIII_a, $rankIII_b);
        $this->assertSame(4, $emp4->tahun_masa_kerja);
        $this->assertSame(0, $emp4->bulan_masa_kerja);
    }

    /**
     * Memeriksa otorisasi pengelolaan pegawai (authorizeEmployeeManagement).
     */
    public function test_authorize_employee_management_memvalidasi_role_dan_unit_kerja(): void
    {
        $reflection = new \ReflectionClass(EmployeeService::class);
        $method = $reflection->getMethod('authorizeEmployeeManagement');
        $method->setAccessible(true);

        // 1. Belum login -> 401
        $this->expectException(HttpException::class);
        $method->invoke($this->service);
    }

    /**
     * Memeriksa Super Admin lolos authorizeEmployeeManagement.
     */
    public function test_authorize_employee_management_lolos_untuk_super_admin(): void
    {
        $superAdmin = new User(['role' => 'super_admin']);
        $this->actingAs($superAdmin);

        $reflection = new \ReflectionClass(EmployeeService::class);
        $method = $reflection->getMethod('authorizeEmployeeManagement');
        $method->setAccessible(true);

        $result = $method->invoke($this->service);
        $this->assertSame('super_admin', $result->role);
    }

    /**
     * Memeriksa Admin tanpa unit kerja ditolak pada authorizeEmployeeManagement.
     */
    public function test_authorize_employee_management_menolak_admin_tanpa_unit_kerja(): void
    {
        $admin = new User(['role' => 'admin', 'kode_unit_kerja' => null]);
        $this->actingAs($admin);

        $reflection = new \ReflectionClass(EmployeeService::class);
        $method = $reflection->getMethod('authorizeEmployeeManagement');
        $method->setAccessible(true);

        $this->expectException(HttpException::class);
        $method->invoke($this->service);
    }

    /**
     * Memeriksa User ditolak pada authorizeEmployeeManagement.
     */
    public function test_authorize_employee_management_menolak_user_biasa(): void
    {
        $user = new User(['role' => 'user']);
        $this->actingAs($user);

        $reflection = new \ReflectionClass(EmployeeService::class);
        $method = $reflection->getMethod('authorizeEmployeeManagement');
        $method->setAccessible(true);

        $this->expectException(HttpException::class);
        $method->invoke($this->service);
    }

    /**
     * Memeriksa otorisasi pegawai per individu (authorizeEmployee).
     */
    public function test_authorize_employee_memvalidasi_kesesuaian_unit_kerja_dan_nip(): void
    {
        // 1. Admin unit A mencoba akses pegawai unit B -> 403
        $adminA = new User(['role' => 'admin', 'kode_unit_kerja' => 'UNIT-A']);
        $this->actingAs($adminA);

        $employeeB = new Employee(['kode_unit_kerja' => 'UNIT-B']);

        try {
            $this->service->authorizeEmployee($employeeB);
            $this->fail('Seharusnya ditolak 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // 2. Admin unit A akses pegawai unit A -> lolos
        $employeeA = new Employee(['kode_unit_kerja' => 'UNIT-A']);
        $this->service->authorizeEmployee($employeeA);
        $this->assertTrue(true);

        // 3. User dengan NIP 123 akses pegawai NIP 999 -> 403
        $userObj = new User(['role' => 'user', 'nip' => '123456789012345678']);
        $this->actingAs($userObj);

        $otherEmployee = new Employee(['nip' => '999999999999999999']);
        try {
            $this->service->authorizeEmployee($otherEmployee);
            $this->fail('Seharusnya ditolak 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // 4. User dengan NIP 123 akses pegawai NIP 123 -> lolos
        $ownEmployee = new Employee(['nip' => '123456789012345678']);
        $this->service->authorizeEmployee($ownEmployee);
        $this->assertTrue(true);
    }

    public function test_public_read_methods_delegate_to_the_repository(): void
    {
        $user = new User(['role' => 'super_admin']);
        $expected = collect();
        $masterExpected = ['departments' => collect()];
        $this->employeeRepository
            ->shouldReceive('getAll')
            ->once()
            ->with($user, ['nip'])
            ->andReturn($expected);
        $this->employeeRepository
            ->shouldReceive('getMasterData')
            ->once()
            ->with($user)
            ->andReturn($masterExpected);
        $this->employeeRepository
            ->shouldReceive('getRanksByEducation')
            ->once()
            ->with('S1', 'PNS')
            ->andReturn($expected);

        $this->assertSame($expected, $this->service->getAll($user, ['nip']));
        $this->assertSame($masterExpected, $this->service->getMasterData($user));
        $this->assertSame($expected, $this->service->getRanksByEducation('S1', 'PNS'));
    }

    public function test_get_by_id_requires_authentication_and_delegates_for_authenticated_user(): void
    {
        $user = new User(['role' => 'super_admin']);
        $employee = new Employee(['nip' => '199001012020011001']);
        $this->actingAs($user);
        $this->employeeRepository
            ->shouldReceive('getById')
            ->once()
            ->with($employee->nip, $user, ['*'])
            ->andReturn($employee);

        $this->assertSame($employee, $this->service->getById($employee->nip));
    }

    public function test_import_rejects_unsupported_file_extension(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));

        try {
            $this->service->import(UploadedFile::fake()->create('pegawai.txt', 10, 'text/plain'));
            $this->fail('Unsupported import files must be rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
    }

    public function test_sync_tmt_completes_when_there_are_no_due_employees(): void
    {
        $this->service->syncTmt();

        $this->assertDatabaseCount('employees', 0);
    }

    public function test_delete_removes_employee_photo_and_delegates_to_repository(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('pegawai/lama.jpg', 'image');
        $user = new User(['role' => 'super_admin']);
        $employee = new Employee([
            'nip' => '199001012020011001',
            'foto' => 'pegawai/lama.jpg',
        ]);
        $this->actingAs($user);
        $this->employeeRepository
            ->shouldReceive('getById')
            ->once()
            ->with($employee->nip, $user)
            ->andReturn($employee);
        $this->employeeRepository
            ->shouldReceive('delete')
            ->once()
            ->with($employee->nip, $user)
            ->andReturn(true);

        $this->assertTrue($this->service->delete($employee->nip));
        Storage::disk('public')->assertMissing('pegawai/lama.jpg');
    }

    public function test_export_and_template_methods_delegate_to_excel_for_super_admin(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);
        $this->employeeRepository
            ->shouldReceive('getExportData')
            ->once()
            ->with($user)
            ->andReturn(collect());
        Excel::shouldReceive('download')
            ->twice()
            ->andReturn('csv-download', 'template-download');

        $this->assertSame('csv-download', $this->service->export('CSV', ['nip']));
        $this->assertSame('template-download', $this->service->downloadTemplate('xlsx'));
    }

    public function test_import_passes_authenticated_user_and_csv_file_to_excel(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $file = UploadedFile::fake()->create('pegawai.csv', 1, 'text/csv');
        $this->actingAs($user);
        $this->employeeImport
            ->shouldReceive('setUser')
            ->once()
            ->with($user);
        Excel::shouldReceive('import')
            ->once()
            ->with($this->employeeImport, Mockery::type(UploadedFile::class));

        $this->service->import($file);
    }
}

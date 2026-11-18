<?php

namespace Tests\Unit\Services;

use App\Imports\EmployeeImport;
use App\Jobs\ResendNotificationJob;
use App\Models\Department;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\EmployeeDocument;
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
use Illuminate\Support\Facades\Bus;
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

        $created = $this->createEmployeesWithPreparedData([[
            'nip' => '199001012020011101',
            'status_pegawai' => 'PNS',
            'tmt_pangkat_lama' => '2020-04-01',
            'tmt_kgb_lama' => '2022-01-01',
            'no_hp' => '6281234567890',
        ]])[0];

        // PNS otomatis mendapat kode_pppk 0
        $this->assertSame('0', $created->kode_pppk);
        // TMT pangkat bertambah 4 tahun
        $this->assertSame('2024-04-01', $created->tmt_pangkat_baru->toDateString());
        // TMT KGB bertambah 2 tahun
        $this->assertSame('2024-01-01', $created->tmt_kgb_baru->toDateString());
        // Nomor HP dinormalisasi
        $this->assertSame('081234567890', $created->no_hp);
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

        $created = $this->createEmployeesWithPreparedData([[
            'nip' => '199001012020011102',
            'status_pegawai' => 'PNS',
            'tmt_pangkat_lama' => null,
            'tmt_kgb_lama' => null,
            'no_hp' => '',
        ]])[0];

        $this->assertNull($created->tmt_pangkat_baru);
        $this->assertNull($created->tmt_kgb_baru);
        $this->assertSame('', $created->no_hp);
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

        $created = $this->createEmployeesWithPreparedData([[
            'nip' => '199001012020011103',
            'status_pegawai' => 'PPPK',
            'tmt_pangkat_lama' => '2020-01-01',
            'tmt_pangkat_baru' => '2024-01-01',
            'tmt_kgb_lama' => '2022-01-01',
        ]])[0];

        $this->assertSame($defaultRank->id, $created->id_pangkat);
        $this->assertNull($created->tmt_pangkat_lama);
        $this->assertNull($created->tmt_pangkat_baru);
        $this->assertSame('2024-01-01', $created->tmt_kgb_baru->toDateString());
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

        $created = $this->createEmployeesWithPreparedData([[
            'nip' => '199001012020011104',
            'tanggal_lahir' => '1968-08-15',
            'kode_jabatan' => 'JAB-01',
        ]])[0];

        // 1968-08-15 + 58 tahun = 2026-08-15
        $this->assertSame('2026-08-15', $created->tanggal_pensiun->toDateString());
        // TMT pensiun efektif tanggal 1 bulan berikutnya: 2026-09-01
        $this->assertSame('2026-09-01', $created->tmt_pensiun->toDateString());
    }

    /**
     * Memeriksa kalkulasi masa pensiun mengembalikan array asli jika parameter tidak lengkap.
     */
    public function test_calculate_retirement_data_mengabaikan_jika_data_tidak_lengkap(): void
    {
        Position::create([
            'kode' => 'JAB-EMPTY-AGE',
            'nama' => 'Jabatan tanpa usia pensiun',
            'usia_pensiun' => 0,
        ]);
        $created = $this->createEmployeesWithPreparedData([
            [
                'nip' => '199001012020011105',
                'kode_jabatan' => 'JAB-01',
            ],
            [
                'nip' => '199001012020011106',
                'tanggal_lahir' => '1970-01-01',
            ],
            [
                'nip' => '199001012020011107',
                'tanggal_lahir' => '1970-01-01',
                'kode_jabatan' => 'NON-EXISTENT',
            ],
            [
                'nip' => '199001012020011108',
                'tanggal_lahir' => '1970-01-01',
                'kode_jabatan' => 'JAB-EMPTY-AGE',
            ],
        ]);

        foreach ($created as $employee) {
            $this->assertNull($employee->tanggal_pensiun);
            $this->assertNull($employee->tmt_pensiun);
        }
    }

    public function test_prepare_employee_data_uses_null_defaults_when_reference_rows_are_missing(): void
    {
        $created = $this->createEmployeesWithPreparedData([
            [
                'nip' => '199001012020011109',
                'status_pegawai' => 'PNS',
                'tmt_pangkat_lama' => '2020-01-01',
                'no_hp' => '08123456789',
            ],
            [
                'nip' => '199001012020011110',
                'status_pegawai' => 'PPPK',
                'id_pangkat' => null,
                'tmt_pangkat_lama' => '2020-01-01',
                'tmt_pangkat_baru' => '2024-01-01',
            ],
        ]);

        $this->assertNull($created[0]->kode_pppk);
        $this->assertSame('08123456789', $created[0]->no_hp);
        $this->assertNull($created[1]->id_pangkat);
        $this->assertNull($created[1]->tmt_pangkat_lama);
        $this->assertNull($created[1]->tmt_pangkat_baru);
        $this->assertNull($created[1]->tmt_kgb_baru);
    }

    public function test_calculate_retirement_data_handles_end_of_month_and_year_rollover_through_create(): void
    {
        Position::create([
            'kode' => 'JAB-58',
            'nama' => 'Jabatan BUP 58',
            'usia_pensiun' => 58,
        ]);

        $created = $this->createEmployeesWithPreparedData([[
            'nip' => '199001012020011111',
            'tanggal_lahir' => '1968-12-31',
            'kode_jabatan' => 'JAB-58',
        ]])[0];

        $this->assertSame('2026-12-31', $created->tanggal_pensiun->toDateString());
        $this->assertSame('2027-01-01', $created->tmt_pensiun->toDateString());
    }

    /*
     * The retirement inputs are exercised above through create(), which invokes
     * prepareEmployeeData() and calculateRetirementData() as one public flow.
     */
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

        $created = $this->createEmployeesWithPreparedData([
            [
                'nip' => '199001012020011112',
                'tanggal_lahir' => '1970-04-15',
                'kode_jabatan' => 'JAB-58',
            ],
            [
                'nip' => '199001012020011113',
                'tanggal_lahir' => '1970-04-15',
                'kode_jabatan' => 'JAB-65',
            ],
        ]);

        $this->assertSame('2028-04-15', $created[0]->tanggal_pensiun->toDateString());
        $this->assertSame('2028-05-01', $created[0]->tmt_pensiun->toDateString());
        $this->assertSame('2035-04-15', $created[1]->tanggal_pensiun->toDateString());
        $this->assertSame('2035-05-01', $created[1]->tmt_pensiun->toDateString());
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
        $rankI_d = Rank::create(['kode' => 'I/d', 'nama' => 'Juru Tingkat I', 'urutan_pangkat' => 4]);
        $rankII_a = Rank::create(['kode' => 'II/a', 'nama' => 'Pengatur Muda', 'urutan_pangkat' => 5]);
        $rankII_d = Rank::create(['kode' => 'II/d', 'nama' => 'Pengatur Tingkat I', 'urutan_pangkat' => 8]);
        $rankIII_a = Rank::create(['kode' => 'III/a', 'nama' => 'Penata Muda', 'urutan_pangkat' => 9]);
        $rankIII_b = Rank::create(['kode' => 'III/b', 'nama' => 'Penata Muda Tingkat I', 'urutan_pangkat' => 10]);
        $rankIII_d = Rank::create(['kode' => 'III/d', 'nama' => 'Penata Tingkat I', 'urutan_pangkat' => 12]);
        $rankIV_a = Rank::create(['kode' => 'IV/a', 'nama' => 'Pembina', 'urutan_pangkat' => 13]);
        $rankI_a = Rank::create(['kode' => 'I/a', 'nama' => 'Juru Muda', 'urutan_pangkat' => 1]);
        $rankV_a = Rank::create(['kode' => 'V/a', 'nama' => 'Golongan Tidak Dikenal', 'urutan_pangkat' => 14]);

        $cases = [
            ['199001012020011201', $rankI_d, $rankII_a, 8, 0],
            ['199001012020011202', $rankII_d, $rankIII_a, 7, 6],
            ['199001012020011203', $rankIII_d, $rankIV_a, 10, 2],
            ['199001012020011204', $rankIII_a, $rankIII_b, 4, 0],
            ['199001012020011205', $rankI_d, $rankII_a, 3, 6],
            ['199001012020011206', $rankII_d, $rankIII_a, 0, 0],
            ['199001012020011207', $rankV_a, $rankI_a, 6, 3],
            ['199001012020011208', $rankI_a, $rankI_d, 4, 0],
            ['199001012020011209', $rankII_a, $rankII_d, 6, 1],
            ['199001012020011210', $rankIII_d, $rankIV_a, 0, 0],
        ];
        $employees = [];
        foreach ($cases as [$nip, $oldRank, $newRank, $years, $months]) {
            $employees[$nip] = new Employee([
                'nip' => $nip,
                'status_pegawai' => 'PNS',
                'id_pangkat' => $oldRank->id,
                'tahun_masa_kerja' => $years,
                'bulan_masa_kerja' => $months,
            ]);
        }

        $actor = new User(['role' => 'super_admin']);
        $this->actingAs($actor);
        $this->employeeRepository
            ->shouldReceive('update')
            ->times(count($cases))
            ->andReturnUsing(function (string $nip, array $data, User $user) use ($employees, $actor): Employee {
                $this->assertSame($actor, $user);
                $employees[$nip]->fill($data);

                return $employees[$nip];
            });
        $this->employeeRepository
            ->shouldReceive('getById')
            ->times(count($cases))
            ->andReturnUsing(fn (string $nip): Employee => $employees[$nip]);
        $this->userSyncService
            ->shouldReceive('sync')
            ->times(count($cases))
            ->andReturnUsing(fn (Employee $employee): User => new User(['nip' => $employee->nip]));

        foreach ($cases as [$nip, $oldRank, $newRank]) {
            $this->service->update($employees[$nip], ['id_pangkat' => $newRank->id]);
        }

        $this->assertSame([2, 0], [
            $employees[$cases[0][0]]->tahun_masa_kerja,
            $employees[$cases[0][0]]->bulan_masa_kerja,
        ]);
        $this->assertSame([2, 6], [
            $employees[$cases[1][0]]->tahun_masa_kerja,
            $employees[$cases[1][0]]->bulan_masa_kerja,
        ]);
        $this->assertSame([10, 1], [
            $employees[$cases[2][0]]->tahun_masa_kerja,
            $employees[$cases[2][0]]->bulan_masa_kerja,
        ]);
        $this->assertSame([4, 0], [
            $employees[$cases[3][0]]->tahun_masa_kerja,
            $employees[$cases[3][0]]->bulan_masa_kerja,
        ]);
        $this->assertSame([0, 0], [
            $employees[$cases[4][0]]->tahun_masa_kerja,
            $employees[$cases[4][0]]->bulan_masa_kerja,
        ]);
        $this->assertSame([0, 0], [
            $employees[$cases[5][0]]->tahun_masa_kerja,
            $employees[$cases[5][0]]->bulan_masa_kerja,
        ]);
        $this->assertSame([6, 3], [
            $employees[$cases[6][0]]->tahun_masa_kerja,
            $employees[$cases[6][0]]->bulan_masa_kerja,
        ]);
        $this->assertSame([4, 0], [
            $employees[$cases[7][0]]->tahun_masa_kerja,
            $employees[$cases[7][0]]->bulan_masa_kerja,
        ]);
        $this->assertSame([6, 1], [
            $employees[$cases[8][0]]->tahun_masa_kerja,
            $employees[$cases[8][0]]->bulan_masa_kerja,
        ]);
        $this->assertSame([0, 0], [
            $employees[$cases[9][0]]->tahun_masa_kerja,
            $employees[$cases[9][0]]->bulan_masa_kerja,
        ]);
    }

    /**
     * Memeriksa otorisasi pengelolaan pegawai (authorizeEmployeeManagement).
     */
    public function test_authorize_employee_management_memvalidasi_role_dan_unit_kerja(): void
    {
        // 1. Belum login -> 401
        $this->assertHttpExceptionStatus(401, fn () => $this->service->create([
            'nip' => '199001012020011211',
        ]));
    }

    /**
     * Memeriksa Super Admin lolos authorizeEmployeeManagement.
     */
    public function test_authorize_employee_management_lolos_untuk_super_admin(): void
    {
        $superAdmin = new User(['role' => 'super_admin']);
        $created = $this->createEmployeesWithPreparedData([[
            'nip' => '199001012020011212',
        ]], $superAdmin)[0];

        $this->assertSame('199001012020011212', $created->nip);
    }

    /**
     * Memeriksa Admin tanpa unit kerja ditolak pada authorizeEmployeeManagement.
     */
    public function test_authorize_employee_management_menolak_admin_tanpa_unit_kerja(): void
    {
        $admin = new User(['role' => 'admin', 'kode_unit_kerja' => null]);
        $this->actingAs($admin);

        $this->assertHttpExceptionStatus(403, fn () => $this->service->create([
            'nip' => '199001012020011213',
        ]));
    }

    /**
     * Memeriksa User ditolak pada authorizeEmployeeManagement.
     */
    public function test_authorize_employee_management_menolak_user_biasa(): void
    {
        $user = new User(['role' => 'user']);
        $this->actingAs($user);

        $this->assertHttpExceptionStatus(403, fn () => $this->service->create([
            'nip' => '199001012020011214',
        ]));
    }

    public function test_authorize_employee_management_rejects_unknown_role_and_allows_admin_with_unit(): void
    {
        $this->actingAs(new User(['role' => 'guest']));
        $this->assertHttpExceptionStatus(403, fn () => $this->service->create([
            'nip' => '199001012020011215',
        ]));

        $admin = new User(['role' => 'admin', 'kode_unit_kerja' => 'UNIT-A']);
        $created = $this->createEmployeesWithPreparedData([[
            'nip' => '199001012020011216',
            'kode_unit_kerja' => 'UNIT-B',
        ]], $admin)[0];

        $this->assertSame('UNIT-A', $created->kode_unit_kerja);
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

    public function test_authorize_employee_handles_authentication_missing_scope_and_unknown_roles(): void
    {
        $employee = new Employee([
            'nip' => '199001012020011217',
            'kode_unit_kerja' => 'UNIT-A',
        ]);
        $this->assertHttpExceptionStatus(
            401,
            fn () => $this->service->authorizeEmployee($employee)
        );

        $this->actingAs(new User(['role' => 'super_admin']));
        $this->assertNull($this->service->authorizeEmployee($employee));

        $this->actingAs(new User(['role' => 'admin']));
        $this->assertHttpExceptionStatus(
            403,
            fn () => $this->service->authorizeEmployee($employee)
        );

        $this->actingAs(new User(['role' => 'user']));
        $this->assertHttpExceptionStatus(
            403,
            fn () => $this->service->authorizeEmployee($employee)
        );

        $this->actingAs(new User(['role' => 'guest']));
        $this->assertHttpExceptionStatus(
            403,
            fn () => $this->service->authorizeEmployee($employee)
        );
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

    public function test_create_applies_default_photo_and_syncs_employee_account(): void
    {
        $user = new User(['role' => 'super_admin']);
        $employee = new Employee(['nip' => '199001012020011099']);
        $this->actingAs($user);
        $this->employeeRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data, User $actor) use ($employee, $user): bool {
                $this->assertSame('images/default-user.png', $data['foto']);
                $this->assertSame('Pegawai Baru', $data['nama']);
                $this->assertSame($user, $actor);
                $employee->fill($data);

                return true;
            })
            ->andReturn($employee);
        $this->userSyncService
            ->shouldReceive('sync')
            ->once()
            ->with($employee, null)
            ->andReturn(new User(['nip' => $employee->nip]));

        $created = $this->service->create([
            'nip' => $employee->nip,
            'nama' => 'Pegawai Baru',
        ]);

        $this->assertSame($employee, $created);
        $this->assertSame('images/default-user.png', $created->foto);
    }

    public function test_update_persists_employee_changes_and_syncs_existing_account(): void
    {
        Bus::fake();
        $user = new User(['role' => 'super_admin']);
        $employee = new Employee([
            'nip' => '199001012020011098',
            'nama' => 'Nama Lama',
            'status_pegawai' => 'PNS',
        ]);
        $this->actingAs($user);
        $this->employeeRepository
            ->shouldReceive('update')
            ->once()
            ->withArgs(function (string $nip, array $data, User $actor) use ($employee, $user): bool {
                $this->assertSame($employee->nip, $nip);
                $this->assertSame('Nama Baru', $data['nama']);
                $this->assertSame($user, $actor);
                $employee->fill($data);

                return true;
            });
        $this->employeeRepository
            ->shouldReceive('getById')
            ->once()
            ->with($employee->nip, $user)
            ->andReturn($employee);
        $this->userSyncService
            ->shouldReceive('sync')
            ->once()
            ->with($employee, $employee->nip)
            ->andReturn(new User(['nip' => $employee->nip]));

        $updated = $this->service->update($employee, ['nama' => 'Nama Baru']);

        $this->assertSame($employee, $updated);
        $this->assertSame('Nama Baru', $updated->nama);
        Bus::assertNotDispatched(ResendNotificationJob::class);
    }

    public function test_update_allows_user_to_change_own_data_but_not_nip(): void
    {
        $nip = '199001012020011218';
        $user = new User(['role' => 'user', 'nip' => $nip]);
        $employee = new Employee([
            'nip' => $nip,
            'nama' => 'Nama Lama',
            'no_hp' => '0811111111',
            'email' => 'lama@example.test',
            'status_pegawai' => 'PNS',
        ]);
        $this->actingAs($user);
        $this->employeeRepository
            ->shouldReceive('update')
            ->once()
            ->withArgs(function (string $oldNip, array $data, User $actor) use ($employee, $user): bool {
                $this->assertSame($employee->nip, $oldNip);
                $this->assertArrayNotHasKey('nip', $data);
                $this->assertSame('Nama Sendiri', $data['nama']);
                $this->assertSame($user, $actor);
                $employee->fill($data);

                return true;
            });
        $this->employeeRepository
            ->shouldReceive('getById')
            ->once()
            ->with($nip, $user)
            ->andReturn($employee);
        $this->userSyncService
            ->shouldReceive('sync')
            ->once()
            ->with($employee, $nip)
            ->andReturn(new User(['nip' => $nip]));

        $updated = $this->service->update($employee, [
            'nip' => '199001012020019999',
            'nama' => 'Nama Sendiri',
        ]);

        $this->assertSame($nip, $updated->nip);
        $this->assertSame('Nama Sendiri', $updated->nama);
    }

    public function test_super_admin_can_change_employee_nip_and_sync_uses_old_nip(): void
    {
        $actor = new User(['role' => 'super_admin']);
        $oldNip = '199001012020011224';
        $newNip = '199001012020019998';
        $employee = new Employee([
            'nip' => $oldNip,
            'status_pegawai' => 'PNS',
        ]);
        $this->actingAs($actor);
        $this->employeeRepository
            ->shouldReceive('update')
            ->once()
            ->withArgs(function (string $nip, array $data, User $user) use ($oldNip, $newNip, $actor, $employee): bool {
                $this->assertSame($oldNip, $nip);
                $this->assertSame($newNip, $data['nip']);
                $this->assertSame($actor, $user);
                $employee->fill($data);

                return true;
            });
        $this->employeeRepository
            ->shouldReceive('getById')
            ->once()
            ->with($newNip, $actor)
            ->andReturn($employee);
        $this->userSyncService
            ->shouldReceive('sync')
            ->once()
            ->with($employee, $oldNip)
            ->andReturn(new User(['nip' => $newNip]));

        $updated = $this->service->update($employee, ['nip' => $newNip]);

        $this->assertSame($newNip, $updated->nip);
    }

    public function test_update_denies_unauthenticated_users_and_users_editing_another_employee(): void
    {
        $employee = new Employee([
            'nip' => '199001012020011219',
            'status_pegawai' => 'PNS',
        ]);
        $this->assertHttpExceptionStatus(
            401,
            fn () => $this->service->update($employee, ['nama' => 'Tidak boleh'])
        );

        $this->actingAs(new User([
            'role' => 'user',
            'nip' => '199001012020011220',
        ]));
        $this->assertHttpExceptionStatus(
            403,
            fn () => $this->service->update($employee, ['nama' => 'Tidak boleh'])
        );
    }

    public function test_update_denies_user_without_linked_nip(): void
    {
        $this->actingAs(new User(['role' => 'user', 'nip' => null]));
        $employee = new Employee([
            'nip' => '199001012020011407',
            'status_pegawai' => 'PNS',
        ]);

        $this->assertHttpExceptionStatus(
            403,
            fn () => $this->service->update($employee, ['nama' => 'Tidak boleh'])
        );
    }

    public function test_update_reschedules_notifications_when_phone_or_email_changes(): void
    {
        Bus::fake();
        $actor = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($actor);
        $employee = $this->persistEmployee('199001012020011223', [
            'no_hp' => '0811111111',
            'email' => 'old-email@example.test',
        ]);
        $service = $this->makeIntegratedEmployeeService();

        $phoneUpdated = $service->update($employee, [
            'no_hp' => '+62 812-2222-3333',
        ]);

        $this->assertSame('081222223333', $phoneUpdated->no_hp);
        $this->assertSame('old-email@example.test', $phoneUpdated->email);
        Bus::assertDispatchedTimes(ResendNotificationJob::class, 1);

        $emailUpdated = $service->update($phoneUpdated, [
            'email' => 'new-email@example.test',
        ]);
        $this->assertSame('081222223333', $emailUpdated->no_hp);
        $this->assertSame('new-email@example.test', $emailUpdated->email);
        Bus::assertDispatched(
            ResendNotificationJob::class,
            fn (ResendNotificationJob $job): bool => $job->nip === $employee->nip
        );
        Bus::assertDispatchedTimes(ResendNotificationJob::class, 2);
    }

    public function test_create_rolls_back_employee_when_account_synchronization_fails(): void
    {
        $actor = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'duplicate-email@example.test',
        ]);
        $this->actingAs($actor);
        $service = $this->makeIntegratedEmployeeService();

        try {
            $service->create([
                'nip' => '199001012020011405',
                'nama' => 'Email Duplikat',
                'email' => 'duplicate-email@example.test',
            ]);
            $this->fail('Employee account synchronization must reject a duplicate email.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertSame(
                'Email pegawai sudah digunakan oleh akun pengguna lain.',
                $exception->errors()['email'][0]
            );
        }

        $this->assertDatabaseMissing('employees', ['nip' => '199001012020011405']);
        $this->assertDatabaseMissing('users', ['nip' => '199001012020011405']);
    }

    public function test_update_rolls_back_employee_when_account_synchronization_fails(): void
    {
        $actor = User::factory()->create(['role' => 'super_admin']);
        User::factory()->create(['email' => 'already-used@example.test']);
        $this->actingAs($actor);
        $employee = $this->persistEmployee('199001012020011408');
        $service = $this->makeIntegratedEmployeeService();

        try {
            $service->update($employee, ['email' => 'already-used@example.test']);
            $this->fail('Employee account synchronization must reject a duplicate email.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertSame(
                'Email pegawai sudah digunakan oleh akun pengguna lain.',
                $exception->errors()['email'][0]
            );
        }

        $this->assertDatabaseHas('employees', [
            'nip' => $employee->nip,
            'email' => null,
        ]);
        $this->assertDatabaseMissing('users', ['nip' => $employee->nip]);
        $this->assertDatabaseHas('users', ['email' => 'already-used@example.test']);
    }

    public function test_update_admin_is_limited_to_its_unit_and_cannot_move_employee(): void
    {
        $admin = new User([
            'role' => 'admin',
            'kode_unit_kerja' => 'UNIT-A',
        ]);
        $sameUnitEmployee = new Employee([
            'nip' => '199001012020011221',
            'kode_unit_kerja' => 'UNIT-A',
            'status_pegawai' => 'PNS',
        ]);
        $this->actingAs($admin);
        $this->employeeRepository
            ->shouldReceive('update')
            ->once()
            ->withArgs(function (string $nip, array $data, User $actor) use ($sameUnitEmployee, $admin): bool {
                $this->assertSame($sameUnitEmployee->nip, $nip);
                $this->assertSame('UNIT-A', $data['kode_unit_kerja']);
                $this->assertSame($admin, $actor);
                $sameUnitEmployee->fill($data);

                return true;
            });
        $this->employeeRepository
            ->shouldReceive('getById')
            ->once()
            ->with($sameUnitEmployee->nip, $admin)
            ->andReturn($sameUnitEmployee);
        $this->userSyncService
            ->shouldReceive('sync')
            ->once()
            ->with($sameUnitEmployee, $sameUnitEmployee->nip)
            ->andReturn(new User(['nip' => $sameUnitEmployee->nip]));

        $updated = $this->service->update($sameUnitEmployee, [
            'kode_unit_kerja' => 'UNIT-B',
        ]);

        $this->assertSame('UNIT-A', $updated->kode_unit_kerja);

        $otherUnitEmployee = new Employee([
            'nip' => '199001012020011222',
            'kode_unit_kerja' => 'UNIT-B',
        ]);
        $this->assertHttpExceptionStatus(
            403,
            fn () => $this->service->update($otherUnitEmployee, ['nama' => 'Ditolak'])
        );
    }

    public function test_create_stores_an_uploaded_photo(): void
    {
        Storage::fake('public');
        $user = new User(['role' => 'super_admin']);
        $employee = new Employee([
            'nip' => '199001012020011097',
            'nama' => 'Pegawai dengan Foto',
        ]);
        $this->actingAs($user);
        $this->employeeRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data, User $actor) use ($employee, $user): bool {
                $this->assertSame($user, $actor);
                $this->assertStringStartsWith('pegawai/', $data['foto']);
                Storage::disk('public')->assertExists($data['foto']);
                $employee->fill($data);

                return true;
            })
            ->andReturn($employee);
        $this->userSyncService
            ->shouldReceive('sync')
            ->once()
            ->with($employee, null)
            ->andReturn(new User(['nip' => $employee->nip]));

        $created = $this->service->create([
            'nip' => $employee->nip,
            'nama' => $employee->nama,
            'foto' => UploadedFile::fake()->image('pegawai.jpg'),
        ]);

        $this->assertSame($employee, $created);
        Storage::disk('public')->assertExists($created->foto);
    }

    public function test_create_persists_employee_account_and_document_in_private_storage(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($actor);
        $service = $this->makeIntegratedEmployeeService();

        $created = $service->create([
            'nip' => '199001012020011401',
            'nama' => 'Pegawai dengan Dokumen',
            'dokumen' => [
                'SK_PANGKAT' => null,
                'SK_KGB' => UploadedFile::fake()->createWithContent('sk-kgb.pdf', 'pdf content'),
            ],
        ]);

        $document = EmployeeDocument::where('nip', $created->nip)->firstOrFail();
        $this->assertDatabaseHas('employees', ['nip' => $created->nip]);
        $this->assertDatabaseHas('users', [
            'nip' => $created->nip,
            'role' => 'user',
        ]);
        $this->assertSame('SK_KGB', $document->jenis_dokumen);
        $this->assertSame('sk-kgb.pdf', $document->nama_file_asli);
        Storage::disk('local')->assertExists($document->nama_file);
    }

    public function test_create_rolls_back_employee_and_cleans_documents_after_later_document_failure(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($actor);
        $service = $this->makeIntegratedEmployeeService();

        try {
            $service->create([
                'nip' => '199001012020011402',
                'nama' => 'Pegawai Gagal',
                'dokumen' => [
                    'SK_KGB' => UploadedFile::fake()->createWithContent('sk-kgb.pdf', 'pdf content'),
                    'JENIS_TIDAK_VALID' => UploadedFile::fake()->createWithContent('lain.pdf', 'pdf content'),
                ],
            ]);
            $this->fail('An unsupported document type must abort employee creation.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('Jenis dokumen tidak diizinkan', $exception->getMessage());
        }

        $this->assertDatabaseMissing('employees', ['nip' => '199001012020011402']);
        $this->assertDatabaseMissing('users', ['nip' => '199001012020011402']);
        $this->assertDatabaseCount('employee_documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('employee-documents/199001012020011402'));
    }

    public function test_update_replaces_uploaded_photo_and_removes_the_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('pegawai/lama.jpg', 'old photo');
        $user = new User(['role' => 'super_admin']);
        $employee = new Employee([
            'nip' => '199001012020011096',
            'nama' => 'Pegawai',
            'status_pegawai' => 'PNS',
            'foto' => 'pegawai/lama.jpg',
        ]);
        $this->actingAs($user);
        $this->employeeRepository
            ->shouldReceive('update')
            ->once()
            ->withArgs(function (string $nip, array $data, User $actor) use ($employee, $user): bool {
                $this->assertSame($employee->nip, $nip);
                $this->assertSame($user, $actor);
                $this->assertStringStartsWith('pegawai/', $data['foto']);
                $this->assertNotSame('pegawai/lama.jpg', $data['foto']);
                $employee->fill($data);

                return true;
            });
        $this->employeeRepository
            ->shouldReceive('getById')
            ->once()
            ->with($employee->nip, $user)
            ->andReturn($employee);
        $this->userSyncService
            ->shouldReceive('sync')
            ->once()
            ->with($employee, $employee->nip)
            ->andReturn(new User(['nip' => $employee->nip]));

        $updated = $this->service->update($employee, [
            'foto' => UploadedFile::fake()->image('baru.jpg'),
        ]);

        $this->assertSame($employee, $updated);
        Storage::disk('public')->assertExists($updated->foto);
        Storage::disk('public')->assertMissing('pegawai/lama.jpg');
    }

    public function test_update_fails_explicitly_when_uploaded_photo_storage_returns_false(): void
    {
        $actor = new User(['role' => 'super_admin']);
        $employee = new Employee([
            'nip' => '199001012020011406',
            'status_pegawai' => 'PNS',
        ]);
        $this->actingAs($actor);
        $publicDisk = Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $publicDisk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')
            ->once()
            ->with('public')
            ->andReturn($publicDisk);
        Storage::shouldReceive('disk')
            ->once()
            ->with('local')
            ->andReturn(Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class));

        try {
            $this->service->update($employee, [
                'foto' => UploadedFile::fake()->image('gagal.jpg'),
            ]);
            $this->fail('A failed photo write must abort the update.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Foto pegawai gagal disimpan.', $exception->getMessage());
        }
    }

    public function test_update_replaces_document_and_deletes_old_file_only_after_success(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($actor);
        $employee = $this->persistEmployee('199001012020011403');
        $oldPath = 'employee-documents/' . $employee->nip . '/old-sk.pdf';
        Storage::disk('local')->put($oldPath, 'old document');
        $oldDocument = EmployeeDocument::create([
            'nip' => $employee->nip,
            'id_user' => $actor->id,
            'jenis_dokumen' => 'SK_KGB',
            'nama_file' => $oldPath,
            'nama_file_asli' => 'old-sk.pdf',
        ]);
        $service = $this->makeIntegratedEmployeeService();

        $updated = $service->update($employee, [
            'dokumen' => [
                'SK_KGB' => UploadedFile::fake()->createWithContent('new-sk.pdf', 'new document'),
            ],
        ]);

        $updatedDocument = EmployeeDocument::where('nip', $updated->nip)
            ->where('jenis_dokumen', 'SK_KGB')
            ->firstOrFail();
        $this->assertSame($oldDocument->id, $updatedDocument->id);
        $this->assertSame('new-sk.pdf', $updatedDocument->nama_file_asli);
        $this->assertNotSame($oldPath, $updatedDocument->nama_file);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($updatedDocument->nama_file);
    }

    public function test_update_failure_rolls_back_document_replacement_and_keeps_old_file(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($actor);
        $employee = $this->persistEmployee('199001012020011404');
        $oldPath = 'employee-documents/' . $employee->nip . '/old-sk.pdf';
        Storage::disk('local')->put($oldPath, 'old document');
        $oldDocument = EmployeeDocument::create([
            'nip' => $employee->nip,
            'id_user' => $actor->id,
            'jenis_dokumen' => 'SK_KGB',
            'nama_file' => $oldPath,
            'nama_file_asli' => 'old-sk.pdf',
        ]);
        $service = $this->makeIntegratedEmployeeService();

        try {
            $service->update($employee, [
                'dokumen' => [
                    'SK_KGB' => UploadedFile::fake()->createWithContent('new-sk.pdf', 'new document'),
                    'JENIS_TIDAK_VALID' => UploadedFile::fake()->createWithContent('invalid.pdf', 'invalid'),
                ],
            ]);
            $this->fail('An unsupported document type must roll back document updates.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('Jenis dokumen tidak diizinkan', $exception->getMessage());
        }

        $this->assertSame($oldPath, $oldDocument->fresh()->nama_file);
        Storage::disk('local')->assertExists($oldPath);
        $this->assertSame(
            [$oldPath],
            Storage::disk('local')->allFiles('employee-documents/' . $employee->nip)
        );
    }

    public function test_attach_schedule_status_handles_retired_non_pns_and_unlinked_pns(): void
    {
        $retiredPppk = new Employee([
            'status' => 'Pensiun',
            'status_pegawai' => 'PPPK',
            'tmt_pensiun' => today()->subDay()->toDateString(),
        ]);
        $retiredStatus = $this->service->attachScheduleStatus($retiredPppk);

        $this->assertTrue($retiredStatus['sudahPensiun']);
        $this->assertFalse($retiredStatus['masaKerjaMaksimum']);
        $this->assertNull($retiredStatus['maksimumMasaKerja']);
        $this->assertFalse($retiredStatus['pangkatMaksimum']);
        $this->assertNull($retiredStatus['batasPangkat']);

        $unlinkedPns = new Employee([
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
        ]);
        $pnsStatus = $this->service->attachScheduleStatus($unlinkedPns);

        $this->assertFalse($pnsStatus['sudahPensiun']);
        $this->assertFalse($pnsStatus['masaKerjaMaksimum']);
        $this->assertNull($pnsStatus['maksimumMasaKerja']);
        $this->assertFalse($pnsStatus['pangkatMaksimum']);
        $this->assertNull($pnsStatus['batasPangkat']);
    }

    public function test_import_rejects_unsupported_file_extension(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        try {
            $this->service->import(
                UploadedFile::fake()->create('pegawai.txt', 10, 'text/plain'),
                $user
            );
            $this->fail('Unsupported import files must be rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
    }

    public function test_sync_tmt_ignores_employees_without_due_dates(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');
        $futurePppk = $this->persistEmployee('199001012020011300', [
            'status_pegawai' => 'PPPK',
            'tmt_kgb_baru' => '2027-06-15',
        ]);
        $futurePns = $this->persistEmployee('199001012020011299', [
            'tmt_pangkat_baru' => '2027-06-15',
            'tmt_pensiun' => '2040-06-15',
        ]);

        $this->service->syncTmt();

        $futurePppk->refresh();
        $futurePns->refresh();
        $this->assertSame('2027-06-15', $futurePppk->tmt_kgb_baru->toDateString());
        $this->assertSame('2027-06-15', $futurePns->tmt_pangkat_baru->toDateString());
        $this->assertSame('Aktif', $futurePns->status);
    }

    public function test_sync_tmt_processes_zero_one_and_multiple_pppk_kgb_periods_and_syncs_user_status(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');

        $zeroLoop = $this->persistEmployee('199001012020011301', [
            'status_pegawai' => 'PPPK',
            'tmt_pangkat_baru' => '2026-06-15',
            'tmt_kgb_baru' => '2027-06-15',
            'tahun_masa_kerja' => 1,
        ]);
        $oneLoop = $this->persistEmployee('199001012020011302', [
            'status_pegawai' => 'PPPK',
            'tmt_kgb_baru' => '2025-06-15',
            'tahun_masa_kerja' => 1,
        ]);
        $multipleLoops = $this->persistEmployee('199001012020011303', [
            'status_pegawai' => 'PPPK',
            'tmt_kgb_baru' => '2020-06-15',
            'tahun_masa_kerja' => 1,
        ]);
        $nonActive = $this->persistEmployee('199001012020011304', [
            'status_pegawai' => 'PPPK',
            'status' => 'Nonaktif',
            'tmt_kgb_baru' => '2025-06-15',
        ]);

        foreach ([$zeroLoop, $oneLoop, $multipleLoops] as $employee) {
            User::factory()->create([
                'role' => 'user',
                'nip' => $employee->nip,
                'status' => false,
            ]);
        }
        User::factory()->create([
            'role' => 'user',
            'nip' => $nonActive->nip,
            'status' => true,
        ]);

        $this->service->syncTmt();

        $zeroLoop->refresh();
        $this->assertSame('2027-06-15', $zeroLoop->tmt_kgb_baru->toDateString());
        $this->assertSame(1, $zeroLoop->tahun_masa_kerja);
        $this->assertDatabaseHas('users', ['nip' => $zeroLoop->nip, 'status' => true]);

        $oneLoop->refresh();
        $this->assertSame('2025-06-15', $oneLoop->tmt_kgb_lama->toDateString());
        $this->assertSame('2027-06-15', $oneLoop->tmt_kgb_baru->toDateString());
        $this->assertSame(3, $oneLoop->tahun_masa_kerja);
        $this->assertDatabaseHas('users', ['nip' => $oneLoop->nip, 'status' => true]);

        $multipleLoops->refresh();
        $this->assertSame('2026-06-15', $multipleLoops->tmt_kgb_lama->toDateString());
        $this->assertSame('2028-06-15', $multipleLoops->tmt_kgb_baru->toDateString());
        $this->assertSame(9, $multipleLoops->tahun_masa_kerja);

        $this->assertDatabaseHas('users', ['nip' => $nonActive->nip, 'status' => false]);
    }

    public function test_sync_tmt_promotes_pns_and_preserves_existing_promotion_history(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');
        $oldRank = Rank::create([
            'kode' => 'I/a',
            'nama' => 'Juru Muda',
            'urutan_pangkat' => 1,
        ]);
        $newRank = Rank::create([
            'kode' => 'I/b',
            'nama' => 'Juru Muda Tingkat I',
            'urutan_pangkat' => 2,
        ]);
        $education = EducationLevel::create([
            'kode' => 'EDU-TEST',
            'nama' => 'Pendidikan Uji',
            'pangkat_pns_minimum' => 1,
            'pangkat_pns_maksimum' => 3,
            'status' => true,
        ]);
        $newHistory = $this->persistEmployee('199001012020011311', [
            'id_pangkat' => $oldRank->id,
            'tingkat_pendidikan' => $education->kode,
            'tmt_pangkat_lama' => '2019-06-15',
            'tmt_pangkat_baru' => '2023-06-15',
        ]);
        $existingHistory = $this->persistEmployee('199001012020011312', [
            'id_pangkat' => $oldRank->id,
            'tingkat_pendidikan' => $education->kode,
            'tmt_pangkat_lama' => '2019-06-15',
            'tmt_pangkat_baru' => '2023-06-15',
        ]);
        PromotionHistory::create([
            'nip' => $existingHistory->nip,
            'id_pangkat_lama' => $oldRank->id,
            'id_pangkat_baru' => $newRank->id,
            'tmt_pangkat' => '2023-06-15',
            'keterangan' => 'Riwayat telah tersedia',
        ]);

        $this->service->syncTmt();

        $newHistory->refresh();
        $existingHistory->refresh();
        $this->assertSame($newRank->id, $newHistory->id_pangkat);
        $this->assertSame('2023-06-15', $newHistory->tmt_pangkat_lama->toDateString());
        $this->assertSame('2027-06-15', $newHistory->tmt_pangkat_baru->toDateString());
        $this->assertSame(1, PromotionHistory::where('nip', $newHistory->nip)->count());
        $this->assertSame($newRank->id, $existingHistory->id_pangkat);
        $this->assertSame(1, PromotionHistory::where('nip', $existingHistory->nip)->count());
    }

    public function test_sync_tmt_clears_pns_promotion_dates_for_missing_or_exceeded_rank_limits(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');
        $currentRank = Rank::create([
            'kode' => 'I/a',
            'nama' => 'Juru Muda',
            'urutan_pangkat' => 1,
        ]);
        $nextRank = Rank::create([
            'kode' => 'I/b',
            'nama' => 'Juru Muda Tingkat I',
            'urutan_pangkat' => 2,
        ]);
        $overLimitNextRank = Rank::create([
            'kode' => 'I/c',
            'nama' => 'Juru',
            'urutan_pangkat' => 4,
        ]);
        $rankWithoutNextRow = Rank::create([
            'kode' => 'I/d',
            'nama' => 'Juru Tingkat I',
            'urutan_pangkat' => 5,
        ]);
        $topRank = Rank::create([
            'kode' => 'IV/e',
            'nama' => 'Pembina Utama',
            'urutan_pangkat' => 17,
        ]);
        $educationAtCurrentLimit = EducationLevel::create([
            'kode' => 'EDU-LIMIT-1',
            'nama' => 'Pendidikan Batas Saat Ini',
            'pangkat_pns_minimum' => 1,
            'pangkat_pns_maksimum' => 1,
            'status' => true,
        ]);
        $educationAllowsTopRank = EducationLevel::create([
            'kode' => 'EDU-LIMIT-18',
            'nama' => 'Pendidikan Batas Atas',
            'pangkat_pns_minimum' => 1,
            'pangkat_pns_maksimum' => 18,
            'status' => true,
        ]);
        $educationNextRankLimit = EducationLevel::create([
            'kode' => 'EDU-LIMIT-2',
            'nama' => 'Pendidikan Pangkat Berikutnya',
            'pangkat_pns_minimum' => 1,
            'pangkat_pns_maksimum' => 2,
            'status' => true,
        ]);
        $educationAllowsBelowNextRank = EducationLevel::create([
            'kode' => 'EDU-LIMIT-3',
            'nama' => 'Pendidikan Batas Pangkat Berikutnya',
            'pangkat_pns_minimum' => 1,
            'pangkat_pns_maksimum' => 3,
            'status' => true,
        ]);
        $educationAllowsMissingNextRow = EducationLevel::create([
            'kode' => 'EDU-LIMIT-6',
            'nama' => 'Pendidikan Pangkat Berikutnya Tidak Ada',
            'pangkat_pns_minimum' => 1,
            'pangkat_pns_maksimum' => 6,
            'status' => true,
        ]);

        $employees = [
            $this->persistEmployee('199001012020011321', [
                'id_pangkat' => null,
                'tingkat_pendidikan' => $educationAllowsTopRank->kode,
                'tmt_pangkat_baru' => '2023-06-15',
            ]),
            $this->persistEmployee('199001012020011322', [
                'id_pangkat' => $currentRank->id,
                'tingkat_pendidikan' => null,
                'tmt_pangkat_baru' => '2023-06-15',
            ]),
            $this->persistEmployee('199001012020011323', [
                'id_pangkat' => $currentRank->id,
                'tingkat_pendidikan' => $educationAtCurrentLimit->kode,
                'tmt_pangkat_baru' => '2023-06-15',
            ]),
            $this->persistEmployee('199001012020011324', [
                'id_pangkat' => $topRank->id,
                'tingkat_pendidikan' => $educationAllowsTopRank->kode,
                'tmt_pangkat_baru' => '2023-06-15',
            ]),
            $this->persistEmployee('199001012020011325', [
                'id_pangkat' => $currentRank->id,
                'tingkat_pendidikan' => $educationNextRankLimit->kode,
                'tmt_pangkat_baru' => '2023-06-15',
            ]),
            $this->persistEmployee('199001012020011326', [
                'id_pangkat' => $nextRank->id,
                'tingkat_pendidikan' => $educationAllowsBelowNextRank->kode,
                'tmt_pangkat_baru' => '2023-06-15',
            ]),
            $this->persistEmployee('199001012020011327', [
                'id_pangkat' => $rankWithoutNextRow->id,
                'tingkat_pendidikan' => $educationAllowsMissingNextRow->kode,
                'tmt_pangkat_baru' => '2023-06-15',
            ]),
        ];

        $this->assertNotNull($overLimitNextRank->id);
        $this->service->syncTmt();

        foreach ($employees as $employee) {
            $employee->refresh();
            $this->assertNull($employee->tmt_pangkat_baru);
        }
        $this->assertSame($currentRank->id, $employees[2]->fresh()->id_pangkat);
        $this->assertSame($nextRank->id, $employees[4]->fresh()->id_pangkat);
        $this->assertSame($nextRank->id, $employees[5]->fresh()->id_pangkat);
        $this->assertSame($rankWithoutNextRow->id, $employees[6]->fresh()->id_pangkat);
    }

    public function test_sync_tmt_retires_due_employees_but_keeps_future_retirement_active(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');
        $due = $this->persistEmployee('199001012020011331', [
            'tmt_pensiun' => '2026-06-15',
        ]);
        $alreadyRetired = $this->persistEmployee('199001012020011332', [
            'status' => 'Pensiun',
            'status_pegawai' => 'PPPK',
            'tmt_pensiun' => '2026-06-15',
            'tmt_kgb_baru' => '2025-06-15',
        ]);
        $futureRetirement = $this->persistEmployee('199001012020011333', [
            'tmt_pensiun' => '2027-06-15',
            'tmt_pangkat_baru' => '2023-06-15',
        ]);

        foreach ([$due, $alreadyRetired, $futureRetirement] as $employee) {
            User::factory()->create([
                'role' => 'user',
                'nip' => $employee->nip,
                'status' => true,
            ]);
        }

        $this->service->syncTmt();

        $due->refresh();
        $alreadyRetired->refresh();
        $futureRetirement->refresh();
        $this->assertSame('Pensiun', $due->status);
        $this->assertSame('Pensiun', $alreadyRetired->status);
        $this->assertSame('2025-06-15', $alreadyRetired->tmt_kgb_baru->toDateString());
        $this->assertSame('Aktif', $futureRetirement->status);
        $this->assertDatabaseHas('users', ['nip' => $due->nip, 'status' => false]);
        $this->assertDatabaseHas('users', ['nip' => $alreadyRetired->nip, 'status' => false]);
    }

    public function test_sync_tmt_processes_employees_across_chunk_boundary(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');
        for ($index = 1; $index <= 101; $index++) {
            $this->persistEmployee(sprintf('199001012020%06d', $index), [
                'status_pegawai' => 'PPPK',
                'tmt_kgb_baru' => '2025-06-15',
            ]);
        }

        $this->service->syncTmt();

        $this->assertSame(101, Employee::where('tahun_masa_kerja', 2)->count());
        $this->assertSame(101, Employee::whereDate('tmt_kgb_baru', '2027-06-15')->count());
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

        $this->service->import($file, $user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createEmployeesWithPreparedData(array $payloads, ?User $actor = null): array
    {
        $actor ??= new User(['role' => 'super_admin']);
        $this->actingAs($actor);
        $createdEmployees = [];
        $this->employeeRepository
            ->shouldReceive('create')
            ->times(count($payloads))
            ->andReturnUsing(function (array $data, User $receivedActor) use ($actor, &$createdEmployees): Employee {
                $this->assertSame($actor, $receivedActor);
                $employee = new Employee();
                $employee->fill($data);
                $createdEmployees[] = $employee;

                return $employee;
            });
        $this->userSyncService
            ->shouldReceive('sync')
            ->times(count($payloads))
            ->andReturnUsing(function (Employee $employee, ?string $oldNip = null): User {
                $this->assertNull($oldNip);

                return new User(['nip' => $employee->nip]);
            });

        foreach ($payloads as $payload) {
            $this->service->create($payload);
        }

        return $createdEmployees;
    }

    private function persistEmployee(string $nip, array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'nip' => $nip,
            'nama' => 'Pegawai Uji',
            'status_pegawai' => 'PNS',
            'status' => 'Aktif',
            'tahun_masa_kerja' => 0,
            'bulan_masa_kerja' => 0,
        ], $overrides));
    }

    private function makeIntegratedEmployeeService(): EmployeeService
    {
        return new EmployeeService(
            new EmployeeRepository(),
            app(EmployeeImport::class),
            new EmployeeUserSyncService()
        );
    }

    private function assertHttpExceptionStatus(int $status, callable $callback): void
    {
        try {
            $callback();
            $this->fail("Expected HTTP {$status} to be thrown.");
        } catch (HttpException $exception) {
            $this->assertSame($status, $exception->getStatusCode());
        }
    }
}

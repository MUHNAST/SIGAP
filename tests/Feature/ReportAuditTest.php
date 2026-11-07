<?php

namespace Tests\Feature;

use App\Exports\EmployeeExport;
use App\Models\Department;
use App\Models\Employee;
use App\Models\KgbHistory;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Rank;
use App\Models\RolePermission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReportAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function grantPermission(string $code, string $role = 'admin'): void
    {
        $permission = Permission::create([
            'kode' => $code,
            'nama' => $code,
            'modul' => 'report_audit',
        ]);

        RolePermission::create([
            'role' => $role,
            'permission_id' => $permission->id,
        ]);
    }

    private function createScheduledEmployee(
        string $nip,
        string $name,
        string $unit,
        ?int $rankId = null
    ): Employee {
        return Employee::create([
            'nip' => $nip,
            'nama' => $name,
            'kode_unit_kerja' => $unit,
            'id_pangkat' => $rankId,
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
            'tanggal_masuk' => '2018-01-01',
            'tmt_kgb_lama' => Carbon::today()->subYears(2)->toDateString(),
            'tmt_kgb_baru' => Carbon::today()->addMonth()->toDateString(),
        ]);
    }

    public function test_kgb_report_filters_are_intersected_with_admin_scope_and_super_admin_sees_all(): void
    {
        Carbon::setTestNow('2026-11-04 12:00:00');
        $unitA = Department::create([
            'kode' => 'RPT-A',
            'nama' => 'Unit Laporan A',
            'status' => true,
        ]);
        $unitB = Department::create([
            'kode' => 'RPT-B',
            'nama' => 'Unit Laporan B',
            'status' => true,
        ]);
        $rank = Rank::create([
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ]);
        $employeeA = $this->createScheduledEmployee(
            '199001012020011101',
            'Pegawai A',
            $unitA->kode,
            $rank->id
        );
        $employeeB = $this->createScheduledEmployee(
            '199001012020011102',
            'Pegawai B',
            $unitB->kode,
            $rank->id
        );
        $this->grantPermission('laporan_kgb.manage');
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => $unitA->kode,
        ]);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->get(route('report.kgb'))
            ->assertOk()
            ->assertViewHas('data', function ($employees) use ($employeeA) {
                return $employees->pluck('nip')->all() === [$employeeA->nip];
            })
            ->assertSee($employeeA->nama)
            ->assertDontSee($employeeB->nama);

        $this->get(route('report.kgb', [
            'department' => $unitB->kode,
            'search' => $employeeB->nama,
        ]))
            ->assertOk()
            ->assertViewHas('data', fn ($employees) => $employees->isEmpty());

        $this->actingAs($superAdmin)
            ->get(route('report.kgb'))
            ->assertOk()
            ->assertViewHas('data', function ($employees) use ($employeeA, $employeeB) {
                return $employees->pluck('nip')->all() === [
                    $employeeA->nip,
                    $employeeB->nip,
                ];
            });
    }

    public function test_user_kgb_report_is_limited_to_linked_employee_and_requires_nip(): void
    {
        Carbon::setTestNow('2026-11-04 12:00:00');
        $department = Department::create([
            'kode' => 'RPT-USER',
            'nama' => 'Unit User Laporan',
            'status' => true,
        ]);
        $ownEmployee = $this->createScheduledEmployee(
            '199001012020011103',
            'Pegawai User',
            $department->kode
        );
        $otherEmployee = $this->createScheduledEmployee(
            '199001012020011104',
            'Pegawai Lain',
            $department->kode
        );
        $user = User::factory()->create([
            'role' => 'user',
            'nip' => $ownEmployee->nip,
            'kode_unit_kerja' => $department->kode,
        ]);

        $this->actingAs($user)
            ->get(route('report.kgb.user'))
            ->assertOk()
            ->assertViewHas('data', function ($employees) use ($ownEmployee) {
                return $employees->pluck('nip')->all() === [$ownEmployee->nip];
            })
            ->assertSee($ownEmployee->nama)
            ->assertDontSee($otherEmployee->nama);

        $this->get(route('report.kgb.user', ['search' => $otherEmployee->nama]))
            ->assertOk()
            ->assertViewHas('data', fn ($employees) => $employees->isEmpty());

        $unlinkedUser = User::factory()->create([
            'role' => 'user',
            'nip' => null,
        ]);
        $this->actingAs($unlinkedUser)
            ->get(route('report.kgb.user'))
            ->assertForbidden();
    }

    public function test_kgb_history_list_and_preview_are_limited_to_admin_unit(): void
    {
        Carbon::setTestNow('2026-11-04 12:00:00');
        $unitA = Department::create([
            'kode' => 'HST-A',
            'nama' => 'Unit Riwayat A',
            'status' => true,
        ]);
        $unitB = Department::create([
            'kode' => 'HST-B',
            'nama' => 'Unit Riwayat B',
            'status' => true,
        ]);
        $rank = Rank::create([
            'kode' => 'II/c',
            'nama' => 'Pengatur',
            'urutan_pangkat' => 6,
        ]);
        Position::create([
            'kode' => 'HST-POS',
            'nama' => 'Analis',
            'usia_pensiun' => 58,
        ]);
        $camatPosition = Position::create([
            'kode' => 'HST-CAMAT',
            'nama' => 'Camat Pekalongan Timur',
            'usia_pensiun' => 58,
        ]);
        Employee::create([
            'nip' => '197001011995011107',
            'nama' => 'Pejabat Kecamatan',
            'kode_unit_kerja' => $unitA->kode,
            'kode_jabatan' => $camatPosition->kode,
            'id_pangkat' => $rank->id,
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
        ]);
        $employeeA = Employee::create([
            'nip' => '199001012020011105',
            'nama' => 'Pegawai Riwayat A',
            'kode_unit_kerja' => $unitA->kode,
            'id_pangkat' => $rank->id,
            'status_pegawai' => 'PNS',
        ]);
        $employeeB = Employee::create([
            'nip' => '199001012020011106',
            'nama' => 'Pegawai Riwayat B',
            'kode_unit_kerja' => $unitB->kode,
            'id_pangkat' => $rank->id,
            'status_pegawai' => 'PNS',
        ]);
        $historyA = KgbHistory::create([
            'nip' => $employeeA->nip,
            'id_pangkat' => $rank->id,
            'nomor_sk_baru' => '800/A/2026',
            'tanggal_sk_baru' => '2026-10-01',
            'tmt_kgb_lama' => '2024-10-01',
            'tmt_kgb_baru' => '2026-10-01',
            'tahun_masa_kerja_lama' => 4,
            'tahun_masa_kerja_baru' => 6,
            'gaji_pokok_lama' => 2800000,
            'gaji_pokok_baru' => 3100000,
        ]);
        KgbHistory::create([
            'nip' => $employeeB->nip,
            'id_pangkat' => $rank->id,
            'nomor_sk_baru' => '800/B/2026',
            'tanggal_sk_baru' => '2026-10-02',
            'tmt_kgb_lama' => '2024-10-02',
            'tmt_kgb_baru' => '2026-10-02',
            'tahun_masa_kerja_lama' => 4,
            'tahun_masa_kerja_baru' => 6,
            'gaji_pokok_lama' => 2800000,
            'gaji_pokok_baru' => 3100000,
        ]);
        $this->grantPermission('riwayat_kgb.manage');
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => $unitA->kode,
        ]);

        $this->actingAs($admin)
            ->get(route('history.kgb'))
            ->assertOk()
            ->assertViewHas('histories', function ($histories) use ($historyA) {
                return $histories->pluck('id')->all() === [$historyA->id];
            })
            ->assertSee($employeeA->nama)
            ->assertDontSee($employeeB->nama);

        $this->get(route('history.preview', $historyA->id))
            ->assertOk()
            ->assertViewIs('report.previewHistory')
            ->assertSee($employeeA->nama)
            ->assertSee('Pejabat Kecamatan')
            ->assertSee('Camat Pekalongan Timur')
            ->assertSee('Pengatur (II/c)');

        $printResponse = $this->get(route('history.print', $historyA->id));
        $printResponse
            ->assertOk()
            ->assertViewIs('report.printHistory')
            ->assertSee('window.print()');
        $this->assertDatabaseHas('activity_logs', [
            'id_user' => $admin->id,
            'aktivitas' => 'Cetak Surat KGB',
        ]);

        $this->get(route('history.preview', $historyA->id + 1))
            ->assertNotFound();
    }

    public function test_history_preview_renders_a_fallback_when_no_camat_is_configured(): void
    {
        $department = Department::create([
            'kode' => 'HST-NO-CAMAT',
            'nama' => 'Unit Tanpa Camat',
            'status' => true,
        ]);
        $rank = Rank::create([
            'kode' => 'II/d',
            'nama' => 'Pengatur Tingkat I',
            'urutan_pangkat' => 7,
        ]);
        $employee = Employee::create([
            'nip' => '199001012020011109',
            'nama' => 'Pegawai Riwayat Tanpa Camat',
            'kode_unit_kerja' => $department->kode,
            'id_pangkat' => $rank->id,
            'status_pegawai' => 'PNS',
        ]);
        $history = KgbHistory::create([
            'nip' => $employee->nip,
            'id_pangkat' => $rank->id,
            'nomor_sk_baru' => '800/N/2026',
            'tanggal_sk_baru' => '2026-10-01',
            'tmt_kgb_lama' => '2024-10-01',
            'tmt_kgb_baru' => '2026-10-01',
            'tahun_masa_kerja_lama' => 4,
            'tahun_masa_kerja_baru' => 6,
            'gaji_pokok_lama' => 2800000,
            'gaji_pokok_baru' => 3100000,
        ]);
        $this->grantPermission('riwayat_kgb.manage');
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => $department->kode,
        ]);

        $this->actingAs($admin)
            ->get(route('history.preview', $history->id))
            ->assertOk()
            ->assertSee('NAMA CAMAT, S.Sos')
            ->assertSee($employee->nama)
            ->assertSee($history->nomor_sk_baru);
    }

    public function test_history_preview_renders_camat_without_rank_and_keeps_letter_data(): void
    {
        $department = Department::create([
            'kode' => 'HST-NO-RANK',
            'nama' => 'Unit Camat Tanpa Pangkat',
            'status' => true,
        ]);
        $position = Position::create([
            'kode' => 'HST-CAMAT-NO-RANK',
            'nama' => 'Camat Unit Uji',
            'usia_pensiun' => 58,
        ]);
        Employee::create([
            'nip' => '197001011995011108',
            'nama' => 'Pejabat Tanpa Pangkat',
            'kode_unit_kerja' => $department->kode,
            'kode_jabatan' => $position->kode,
            'id_pangkat' => null,
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
        ]);
        $rank = Rank::create([
            'kode' => 'II/e',
            'nama' => 'Pengatur Tingkat I',
            'urutan_pangkat' => 8,
        ]);
        $employee = Employee::create([
            'nip' => '199001012020011110',
            'nama' => 'Pegawai Riwayat Tanpa Pangkat Camat',
            'kode_unit_kerja' => $department->kode,
            'id_pangkat' => $rank->id,
            'status_pegawai' => 'PNS',
        ]);
        $history = KgbHistory::create([
            'nip' => $employee->nip,
            'id_pangkat' => $rank->id,
            'nomor_sk_baru' => '800/NR/2026',
            'tanggal_sk_baru' => '2026-10-03',
            'tmt_kgb_lama' => '2024-10-03',
            'tmt_kgb_baru' => '2026-10-03',
            'tahun_masa_kerja_lama' => 4,
            'tahun_masa_kerja_baru' => 6,
            'gaji_pokok_lama' => 2800000,
            'gaji_pokok_baru' => 3100000,
        ]);
        $this->grantPermission('riwayat_kgb.manage');
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => $department->kode,
        ]);

        $this->actingAs($admin)
            ->get(route('history.preview', $history->id))
            ->assertOk()
            ->assertSee('Pejabat Tanpa Pangkat')
            ->assertSee('Camat Unit Uji')
            ->assertSee('Pegawai Riwayat Tanpa Pangkat Camat')
            ->assertSee('800/NR/2026');
    }

    public function test_employee_csv_export_uses_selected_columns_filename_and_admin_unit_scope(): void
    {
        Carbon::setTestNow('2026-11-04 12:00:00');
        Excel::fake();
        $unitA = Department::create([
            'kode' => 'EXP-A',
            'nama' => 'Unit Export A',
            'status' => true,
        ]);
        $unitB = Department::create([
            'kode' => 'EXP-B',
            'nama' => 'Unit Export B',
            'status' => true,
        ]);
        $employeeA = Employee::create([
            'nip' => '001234567890123456',
            'nik' => '0012345678901234',
            'nama' => 'Pegawai Export A',
            'kode_unit_kerja' => $unitA->kode,
        ]);
        $employeeB = Employee::create([
            'nip' => '001234567890123457',
            'nama' => 'Pegawai Export B',
            'kode_unit_kerja' => $unitB->kode,
        ]);
        $this->grantPermission('pegawai.export');
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => $unitA->kode,
        ]);

        $this->actingAs($admin)
            ->get(route('employee.export', 'csv').'?columns[]=nip&columns[]=nama&columns[]=nik')
            ->assertOk();

        Excel::assertDownloaded(
            'pegawai-20261104-120000.csv',
            function (EmployeeExport $export) use ($employeeA, $employeeB) {
                $rows = $export->array();
                $employeeRows = array_values(array_filter(
                    $rows,
                    fn (array $row) => ($row[0] ?? null) === 1
                ));

                return $export->headings() === ['No.', 'NIP', 'Nama', 'NIK']
                    && count($employeeRows) === 1
                    && $employeeRows[0] === [
                        1,
                        $employeeA->nip,
                        $employeeA->nama,
                        $employeeA->nik,
                    ]
                    && !str_contains(json_encode($rows), $employeeB->nip);
            }
        );
    }

    public function test_employee_pdf_export_returns_a_downloadable_pdf_and_rejects_user_role(): void
    {
        Carbon::setTestNow('2026-11-04 12:00:00');
        $department = Department::create([
            'kode' => 'EXP-PDF',
            'nama' => 'Unit Export PDF',
            'status' => true,
        ]);
        Employee::create([
            'nip' => '199001012020011108',
            'nama' => 'Pegawai PDF',
            'kode_unit_kerja' => $department->kode,
        ]);
        $this->grantPermission('pegawai.export');
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => $department->kode,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('employee.export', 'pdf').'?columns[]=nip&columns[]=nama');

        $response->assertOk();
        $this->assertSame(
            'application/pdf',
            $response->headers->get('content-type')
        );
        $this->assertStringContainsString(
            'pegawai-20261104-120000.pdf',
            $response->headers->get('content-disposition')
        );
        $this->assertStringStartsWith(
            '%PDF-',
            $response->getContent()
        );

        $user = User::factory()->create([
            'role' => 'user',
            'nip' => '199001012020011108',
        ]);
        $this->actingAs($user)
            ->get(route('employee.export', 'pdf').'?columns[]=nip')
            ->assertForbidden();
    }
}

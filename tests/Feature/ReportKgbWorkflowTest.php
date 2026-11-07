<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Department;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\KgbHistory;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Pppk;
use App\Models\Province;
use App\Models\Rank;
use App\Models\RolePermission;
use App\Models\Salary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportKgbWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function setupMasterData(): array
    {
        $dept = Department::create(['kode' => 'KEC-01', 'nama' => 'Kecamatan Pekalongan Timur', 'status' => true]);
        $rank = Rank::create(['kode' => 'III/a', 'nama' => 'Penata Muda', 'urutan_pangkat' => 9]);
        $pos = Position::create(['kode' => 'CAMAT-01', 'nama' => 'Camat Pekalongan Timur', 'usia_pensiun' => 58]);

        // Camat employee
        $camat = Employee::create([
            'nip' => '197001011995011001',
            'nama' => 'Bapak Camat',
            'kode_unit_kerja' => 'KEC-01',
            'kode_jabatan' => 'CAMAT-01',
            'id_pangkat' => $rank->id,
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
        ]);

        // Salary scales
        Salary::create(['id_pangkat' => $rank->id, 'tahun_masa_kerja' => 2, 'gaji_pokok' => 2800000]);
        Salary::create(['id_pangkat' => $rank->id, 'tahun_masa_kerja' => 4, 'gaji_pokok' => 3100000]);

        return compact('dept', 'rank', 'pos', 'camat');
    }

    /**
     * Memeriksa Admin tanpa permission laporan_kgb.manage ditolak 403 saat mengakses laporan KGB.
     */
    public function test_admin_tanpa_permission_ditolak_mengakses_laporan_kgb(): void
    {
        $this->setupMasterData();
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'KEC-01',
        ]);

        $response = $this->actingAs($admin)->get(route('report.kgb'));

        $response->assertStatus(403);
    }

    /**
     * Memeriksa Admin dengan permission laporan_kgb.manage dapat membuka halaman daftar KGB.
     */
    public function test_admin_dengan_permission_dapat_melihat_laporan_kgb(): void
    {
        $this->setupMasterData();
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'KEC-01',
        ]);

        $perm = Permission::create(['kode' => 'laporan_kgb.manage', 'nama' => 'Kelola KGB', 'modul' => 'laporan_kgb']);
        RolePermission::create(['role' => 'admin', 'permission_id' => $perm->id]);

        $response = $this->actingAs($admin)->get(route('report.kgb'));

        $response->assertStatus(200);
        $response->assertViewIs('report.index');
    }

    public function test_laporan_kgb_hanya_memuat_pns_yang_memenuhi_jendela_jadwal(): void
    {
        Carbon::setTestNow('2026-11-04 10:00:00');
        $master = $this->setupMasterData();
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'KEC-01',
        ]);
        $permission = Permission::create([
            'kode' => 'laporan_kgb.manage',
            'nama' => 'Kelola KGB',
            'modul' => 'laporan_kgb',
        ]);
        RolePermission::create(['role' => 'admin', 'permission_id' => $permission->id]);

        $eligibleToday = Employee::create([
            'nip' => '199202022018011011',
            'nama' => 'PNS TMT Hari Ini',
            'kode_unit_kerja' => 'KEC-01',
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
            'tanggal_masuk' => '2018-01-01',
            'id_pangkat' => $master['rank']->id,
            'tmt_kgb_lama' => Carbon::today()->subYears(2)->toDateString(),
            'tmt_kgb_baru' => Carbon::today()->toDateString(),
        ]);
        $eligibleAtBoundary = Employee::create([
            'nip' => '199202022018011012',
            'nama' => 'PNS Batas Dua Bulan',
            'kode_unit_kerja' => 'KEC-01',
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
            'tanggal_masuk' => '2018-01-01',
            'id_pangkat' => $master['rank']->id,
            'tmt_kgb_lama' => Carbon::today()->subYears(2)->toDateString(),
            'tmt_kgb_baru' => Carbon::today()->addMonthsNoOverflow(2)->toDateString(),
        ]);
        Employee::create([
            'nip' => '199202022018011013',
            'nama' => 'PNS Tanpa Tanggal Masuk',
            'kode_unit_kerja' => 'KEC-01',
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
            'id_pangkat' => $master['rank']->id,
            'tmt_kgb_baru' => Carbon::today()->addMonth()->toDateString(),
        ]);
        Employee::create([
            'nip' => '199202022018011014',
            'nama' => 'PPPK',
            'kode_unit_kerja' => 'KEC-01',
            'status' => 'Aktif',
            'status_pegawai' => 'PPPK',
            'tanggal_masuk' => '2018-01-01',
            'tmt_kgb_baru' => Carbon::today()->addMonth()->toDateString(),
        ]);
        Employee::create([
            'nip' => '199202022018011015',
            'nama' => 'PNS Di Luar Jendela',
            'kode_unit_kerja' => 'KEC-01',
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
            'tanggal_masuk' => '2018-01-01',
            'id_pangkat' => $master['rank']->id,
            'tmt_kgb_baru' => Carbon::today()->addMonthsNoOverflow(3)->toDateString(),
        ]);
        KgbHistory::create([
            'nip' => $eligibleToday->nip,
            'id_pangkat' => $master['rank']->id,
            'tmt_kgb_baru' => Carbon::today()->toDateString(),
        ]);

        $response = $this->actingAs($admin)->get(route('report.kgb'));

        $response->assertOk()
            ->assertViewHas('jumlahKgb', 1)
            ->assertViewHas('data', function ($employees) use ($eligibleAtBoundary) {
                return $employees->pluck('nip')->all() === [$eligibleAtBoundary->nip];
            })
            ->assertSee(
                Carbon::parse($eligibleAtBoundary->tmt_kgb_baru)
                    ->translatedFormat('d F Y')
            );
    }

    public function test_laporan_kgb_gagal_dirender_jika_tmt_kgb_lama_kosong(): void
    {
        Carbon::setTestNow('2026-11-04 10:00:00');
        $master = $this->setupMasterData();
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'KEC-01',
        ]);
        $permission = Permission::create([
            'kode' => 'laporan_kgb.manage',
            'nama' => 'Kelola KGB',
            'modul' => 'laporan_kgb',
        ]);
        RolePermission::create(['role' => 'admin', 'permission_id' => $permission->id]);
        $employee = Employee::create([
            'nip' => '199202022018011016',
            'nama' => 'PNS Tanpa TMT KGB Lama',
            'kode_unit_kerja' => 'KEC-01',
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
            'tanggal_masuk' => '2018-01-01',
            'id_pangkat' => $master['rank']->id,
            'tmt_kgb_baru' => Carbon::today()->addMonth()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->get(route('report.kgb'))
            ->assertOk()
            ->assertViewHas('data', function ($employees) use ($employee) {
                return $employees->sole()->nip === $employee->nip
                    && $employees->sole()->tanggal_kgb === null;
            })
            ->assertSee($employee->nama)
            ->assertSeeInOrder([$employee->nama, '-', '-', '-']);
    }

    /**
     * Memeriksa preview surat KGB ditolak jika tanggal KGB masih lebih dari 2 bulan ke depan.
     */
    public function test_preview_kgb_ditolak_jika_diluar_jendela_waktu_2_bulan(): void
    {
        $master = $this->setupMasterData();
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'KEC-01',
        ]);

        $perm = Permission::create(['kode' => 'laporan_kgb.manage', 'nama' => 'Kelola KGB', 'modul' => 'laporan_kgb']);
        RolePermission::create(['role' => 'admin', 'permission_id' => $perm->id]);

        $employee = Employee::create([
            'nip' => '199202022018011005',
            'nama' => 'Pegawai Belum Saatnya KGB',
            'kode_unit_kerja' => 'KEC-01',
            'id_pangkat' => $master['rank']->id,
            'status_pegawai' => 'PNS',
            'tmt_kgb_baru' => Carbon::today()->addMonths(4)->format('Y-m-d'), // 4 bulan ke depan
        ]);

        $response = $this->actingAs($admin)->get(route('report.preview', $employee->nip));

        $response->assertStatus(403);
    }

    /**
     * Memeriksa alur cetak surat KGB berhasil, menambah masa kerja, memajukan TMT KGB, dan mencegah cetak ganda.
     */
    public function test_alur_cetak_kgb_membuat_riwayat_dan_mencegah_cetak_ulang(): void
    {
        $master = $this->setupMasterData();
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'KEC-01',
        ]);

        $perm = Permission::create(['kode' => 'laporan_kgb.manage', 'nama' => 'Kelola KGB', 'modul' => 'laporan_kgb']);
        RolePermission::create(['role' => 'admin', 'permission_id' => $perm->id]);

        $tmtTarget = Carbon::today()->addMonth()->format('Y-m-d');

        $employee = Employee::create([
            'nip' => '199202022018011006',
            'nama' => 'Pegawai Siap KGB',
            'kode_unit_kerja' => 'KEC-01',
            'id_pangkat' => $master['rank']->id,
            'status_pegawai' => 'PNS',
            'tahun_masa_kerja' => 2,
            'bulan_masa_kerja' => 0,
            'tmt_kgb_lama' => Carbon::today()->subYears(2)->format('Y-m-d'),
            'tmt_kgb_baru' => $tmtTarget,
        ]);

        // 1. Cetak pertama kali
        $payload = [
            'nomor_sk_baru' => '800/123/2026',
            'tanggal_sk_baru' => now()->format('Y-m-d'),
            'pejabat' => 'Camat Pekalongan Timur',
        ];

        $response1 = $this->actingAs($admin)->post(route('report.print', $employee->nip), $payload);

        $response1->assertStatus(200);
        $response1->assertViewIs('report.print');
        $response1
            ->assertSee('@page')
            ->assertSee('210mm 330mm')
            ->assertSee('KECAMATAN PEKALONGAN TIMUR')
            ->assertSee('window.print()');

        // Memastikan record riwayat KGB terbentuk
        $this->assertDatabaseHas('kgb_histories', [
            'nip' => $employee->nip,
            'tahun_masa_kerja_baru' => 4, // 2 + 2 tahun
        ]);

        $latestHistory = KgbHistory::where('nip', $employee->nip)->latest('id')->first();
        $this->assertNotNull($latestHistory);
        $this->assertStringStartsWith($tmtTarget, (string) $latestHistory->tmt_kgb_baru);

        // Memastikan data pegawai diperbarui
        $employee->refresh();
        $this->assertSame(4, (int) $employee->tahun_masa_kerja);
        $this->assertStringStartsWith(Carbon::parse($tmtTarget)->addYears(2)->format('Y-m-d'), (string) $employee->tmt_kgb_baru);

        // 2. Percobaan cetak kedua kali untuk pegawai yang sama langsung ditolak (403) karena TMT KGB baru sudah maju 2 tahun ke depan
        $response2 = $this->actingAs($admin)->post(route('report.print', $employee->nip), $payload);

        $response2->assertStatus(403);
    }

    /**
     * Memeriksa akun User dilarang melakukan preview KGB milik pegawai lain.
     */
    public function test_user_dilarang_preview_kgb_milik_pegawai_lain(): void
    {
        $master = $this->setupMasterData();

        $ownEmployee = Employee::create([
            'nip' => '199301012019011001',
            'nama' => 'User Saya',
            'kode_unit_kerja' => 'KEC-01',
            'status_pegawai' => 'PNS',
        ]);

        $otherEmployee = Employee::create([
            'nip' => '199301012019011002',
            'nama' => 'User Lain',
            'kode_unit_kerja' => 'KEC-01',
            'status_pegawai' => 'PNS',
            'tmt_kgb_baru' => Carbon::today()->addMonth()->format('Y-m-d'),
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'nip' => $ownEmployee->nip,
            'kode_unit_kerja' => 'KEC-01',
        ]);

        $response = $this->actingAs($user)->get(route('report.kgb.user.preview', $otherEmployee->nip));

        $response->assertStatus(403);
    }
}

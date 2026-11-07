<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Rank;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createDepartment(): Department
    {
        return Department::create([
            'kode' => 'KEC-01',
            'nama' => 'Kecamatan Pekalongan Timur',
            'status' => true,
        ]);
    }

    /**
     * Memeriksa guest dialihkan ke login saat mengakses dashboard admin.
     */
    public function test_guest_dialihkan_ke_login_saat_akses_dashboard_admin(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Memeriksa Super Admin dapat membuka dashboard admin.
     */
    public function test_super_admin_dapat_melihat_dashboard_admin(): void
    {
        $dept = $this->createDepartment();
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'kode_unit_kerja' => $dept->kode,
        ]);

        $this->mock(\App\Services\DashboardService::class, function ($mock) {
            $mock->shouldReceive('getAdminDashboardData')
                ->once()
                ->andReturn([
                    'tahunReminder' => 2026,
                    'tahunRiwayat' => 2026,
                    'tahunList' => [2026, 2025],
                    'pegawaiPensiun' => 5,
                    'pegawaiAktif' => 50,
                    'pegawaiNonaktif' => 2,
                    'totalPNS' => 35,
                    'totalPPPK' => 15,
                    'naikPangkat' => 4,
                    'kgb' => 6,
                    'pensiun' => 3,
                    'aktivitas' => collect(),
                    'notifikasi' => collect(),
                    'chartJenisPegawai' => [],
                    'ChartJenisKelamin' => [],
                    'chartReminder' => [],
                    'chartRiwayat' => [],
                    'chartPangkat' => [],
                    'chartGolonganPppk' => [],
                    'chartPendidikan' => [],
                    'chartStatus' => [],
                    'chartMasaKerja' => [],
                    'chartUsia' => [],
                ]);
        });

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.dashboard_admin');
        $response->assertViewHas('totalPNS');
        $response->assertViewHas('totalPPPK');
        $response->assertViewHas('naikPangkat');
        $response->assertViewHas('kgb');
        $response->assertViewHas('pensiun');
    }

    /**
     * Memeriksa Admin dapat membuka dashboard admin untuk unit kerjanya.
     */
    public function test_admin_dapat_melihat_dashboard_admin(): void
    {
        $dept = $this->createDepartment();
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => $dept->kode,
        ]);

        $this->mock(\App\Services\DashboardService::class, function ($mock) {
            $mock->shouldReceive('getAdminDashboardData')
                ->once()
                ->andReturn([
                    'tahunReminder' => 2026,
                    'tahunRiwayat' => 2026,
                    'tahunList' => [2026],
                    'pegawaiPensiun' => 1,
                    'pegawaiAktif' => 10,
                    'pegawaiNonaktif' => 0,
                    'totalPNS' => 8,
                    'totalPPPK' => 2,
                    'naikPangkat' => 1,
                    'kgb' => 2,
                    'pensiun' => 1,
                    'aktivitas' => collect(),
                    'notifikasi' => collect(),
                    'chartJenisPegawai' => [],
                    'ChartJenisKelamin' => [],
                    'chartReminder' => [],
                    'chartRiwayat' => [],
                    'chartPangkat' => [],
                    'chartGolonganPppk' => [],
                    'chartPendidikan' => [],
                    'chartStatus' => [],
                    'chartMasaKerja' => [],
                    'chartUsia' => [],
                ]);
        });

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.dashboard_admin');
    }

    /**
     * Memeriksa akun User ditolak 403 saat mencoba mengakses dashboard admin.
     */
    public function test_user_ditolak_mengakses_dashboard_admin(): void
    {
        $dept = $this->createDepartment();
        $user = User::factory()->create([
            'role' => 'user',
            'kode_unit_kerja' => $dept->kode,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(403);
    }

    /**
     * Memeriksa akun User dapat membuka dashboard user dengan data jadwal pribadi.
     */
    public function test_user_dapat_melihat_dashboard_user_pribadi(): void
    {
        $dept = $this->createDepartment();
        $rank = Rank::create(['kode' => 'III/a', 'nama' => 'Penata Muda', 'urutan_pangkat' => 9]);

        $employee = Employee::create([
            'nip' => '199501012020011009',
            'nama' => 'Pegawai User',
            'kode_unit_kerja' => $dept->kode,
            'id_pangkat' => $rank->id,
            'status_pegawai' => 'PNS',
            'tmt_kgb_baru' => Carbon::today()->addMonths(2)->format('Y-m-d'),
            'tmt_pangkat_baru' => Carbon::today()->addYears(2)->format('Y-m-d'),
            'tmt_pensiun' => Carbon::today()->addYears(20)->format('Y-m-d'),
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'nip' => $employee->nip,
            'kode_unit_kerja' => $dept->kode,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard_user'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.dashboard_user');
        $response->assertViewHas('kgb');
        $response->assertViewHas('promotion');
        $response->assertViewHas('retirement');
    }

    /**
     * Memeriksa Admin ditolak 403 saat mencoba membuka dashboard user.
     */
    public function test_admin_ditolak_mengakses_dashboard_user(): void
    {
        $dept = $this->createDepartment();
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => $dept->kode,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard_user'));

        $response->assertStatus(403);
    }
}

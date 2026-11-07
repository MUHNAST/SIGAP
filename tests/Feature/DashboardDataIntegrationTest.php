<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\User;
use App\Repositories\CardRepository;
use App\Repositories\DashboardRepository;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardDataIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_dashboard_schedule_counts_match_cards_and_are_scoped_by_unit(): void
    {
        Carbon::setTestNow('2026-11-04 10:00:00');
        $firstDepartment = Department::create([
            'kode' => 'UNIT-A',
            'nama' => 'Unit A',
            'status' => true,
        ]);
        $secondDepartment = Department::create([
            'kode' => 'UNIT-B',
            'nama' => 'Unit B',
            'status' => true,
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => $firstDepartment->kode,
        ]);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $scheduledDate = Carbon::today()->addMonth()->toDateString();

        foreach ([$firstDepartment, $secondDepartment] as $department) {
            Employee::create([
                'nip' => $department->kode === 'UNIT-A'
                    ? '199001012020011101'
                    : '199001012020011102',
                'nama' => 'Pegawai '.$department->kode,
                'kode_unit_kerja' => $department->kode,
                'status' => 'Aktif',
                'status_pegawai' => 'PNS',
                'tmt_pangkat_baru' => $scheduledDate,
                'tmt_kgb_baru' => $scheduledDate,
                'tmt_pensiun' => Carbon::today()->addMonths(5)->toDateString(),
            ]);
        }

        $dashboard = new DashboardRepository();
        $cards = new CardRepository();

        foreach (['kp' => 'naikPangkat', 'kgb' => 'kgb', 'mp' => 'pensiun'] as $type => $counter) {
            $this->assertSame(1, $dashboard->{$counter}($admin));
            $this->assertCount(1, $cards->getUpcoming($type, $admin));
            $this->assertSame(2, $dashboard->{$counter}($superAdmin));
            $this->assertCount(2, $cards->getUpcoming($type, $superAdmin));
        }
    }

    public function test_promotion_dashboard_agenda_excludes_pppk(): void
    {
        Carbon::setTestNow('2026-11-04 10:00:00');
        $department = Department::create([
            'kode' => 'UNIT-P',
            'nama' => 'Unit PPPK',
            'status' => true,
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => $department->kode,
        ]);
        Employee::create([
            'nip' => '199001012020011203',
            'nama' => 'PPPK Dengan TMT Pangkat Lama',
            'kode_unit_kerja' => $department->kode,
            'status' => 'Aktif',
            'status_pegawai' => 'PPPK',
            'tmt_pangkat_baru' => Carbon::today()->addMonth()->toDateString(),
        ]);

        $this->assertSame(0, (new DashboardRepository())->naikPangkat($admin));
    }

    public function test_promotion_card_agenda_excludes_pppk(): void
    {
        Carbon::setTestNow('2026-11-04 10:00:00');
        $department = Department::create([
            'kode' => 'UNIT-C',
            'nama' => 'Unit Kartu',
            'status' => true,
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => $department->kode,
        ]);
        Employee::create([
            'nip' => '199001012020011204',
            'nama' => 'PPPK Untuk Kartu',
            'kode_unit_kerja' => $department->kode,
            'status' => 'Aktif',
            'status_pegawai' => 'PPPK',
            'tmt_pangkat_baru' => Carbon::today()->addMonth()->toDateString(),
        ]);

        $this->assertCount(0, (new CardRepository())->getUpcoming('kp', $admin));
    }

    public function test_promotion_kpi_and_card_include_pns_but_exclude_pppk(): void
    {
        Carbon::setTestNow('2026-11-04 10:00:00');
        $department = Department::create([
            'kode' => 'UNIT-PNS',
            'nama' => 'Unit Perbandingan',
            'status' => true,
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => $department->kode,
        ]);
        $scheduledDate = Carbon::today()->addMonth()->toDateString();
        Employee::create([
            'nip' => '199001012020011207',
            'nama' => 'PPPK Untuk Perbandingan',
            'kode_unit_kerja' => $department->kode,
            'status' => 'Aktif',
            'status_pegawai' => 'PPPK',
            'tmt_pangkat_baru' => $scheduledDate,
        ]);
        $pns = Employee::create([
            'nip' => '199001012020011208',
            'nama' => 'PNS Untuk Perbandingan',
            'kode_unit_kerja' => $department->kode,
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
            'tmt_pangkat_baru' => $scheduledDate,
        ]);

        $this->assertSame(1, (new DashboardRepository())->naikPangkat($admin));
        $this->assertSame(
            [$pns->nip],
            (new CardRepository())->getUpcoming('kp', $admin)->pluck('nip')->all()
        );
    }

    public function test_user_dashboard_data_contains_only_the_authenticated_users_records(): void
    {
        $department = Department::create([
            'kode' => 'UNIT-U',
            'nama' => 'Unit User',
            'status' => true,
        ]);
        $ownEmployee = Employee::create([
            'nip' => '199001012020011201',
            'nama' => 'Pegawai Sendiri',
            'kode_unit_kerja' => $department->kode,
            'status_pegawai' => 'PNS',
            'tmt_kgb_baru' => Carbon::today()->addMonth()->toDateString(),
            'tmt_pangkat_baru' => Carbon::today()->addYears(2)->toDateString(),
            'tmt_pensiun' => Carbon::today()->addYears(20)->toDateString(),
        ]);
        $otherEmployee = Employee::create([
            'nip' => '199001012020011202',
            'nama' => 'Pegawai Lain',
            'kode_unit_kerja' => $department->kode,
            'status_pegawai' => 'PPPK',
            'tmt_pangkat_baru' => Carbon::today()->addMonth()->toDateString(),
        ]);
        $user = User::factory()->create([
            'role' => 'user',
            'nip' => $ownEmployee->nip,
            'kode_unit_kerja' => $department->kode,
        ]);
        $otherUser = User::factory()->create([
            'role' => 'user',
            'nip' => $otherEmployee->nip,
            'kode_unit_kerja' => $department->kode,
        ]);

        foreach ([$ownEmployee, $otherEmployee] as $employee) {
            Notification::create([
                'nip' => $employee->nip,
                'jenis' => 'KGB',
                'judul' => 'Pemberitahuan KGB '.$employee->nama,
                'pesan' => 'Pengingat',
                'tanggal_notifikasi' => Carbon::today()->toDateString(),
                'tanggal_referensi' => Carbon::today()->addMonth()->toDateString(),
            ]);
        }
        ActivityLog::create([
            'id_user' => $user->id,
            'aktivitas' => 'Aktivitas sendiri',
        ]);
        ActivityLog::create([
            'id_user' => $otherUser->id,
            'aktivitas' => 'Aktivitas pengguna lain',
        ]);

        $data = (new DashboardService(new DashboardRepository()))
            ->getUserDashboardData($user);
        $otherData = (new DashboardService(new DashboardRepository()))
            ->getUserDashboardData($otherUser);

        $this->assertSame($ownEmployee->nip, $data['employee']->nip);
        $this->assertSame('Menunggu', $data['kgb']['status']);
        $this->assertSame('Menunggu', $data['promotion']['status']);
        $this->assertSame('Menunggu', $data['retirement']['status']);
        $this->assertSame('Belum dijadwalkan', $otherData['promotion']['status']);
        $this->assertSame(
            [$ownEmployee->nip],
            $data['notifikasi']->pluck('nip')->all()
        );
        $this->assertSame(
            ['Aktivitas sendiri'],
            $data['aktivitas']->pluck('aktivitas')->all()
        );
    }
}

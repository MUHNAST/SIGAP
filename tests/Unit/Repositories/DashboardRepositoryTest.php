<?php

namespace Tests\Unit\Repositories;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Repositories\DashboardRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_empty_state_returns_zero_counts_and_stable_chart_shapes(): void
    {
        $repository = new DashboardRepository();
        $user = new User(['role' => 'user', 'nip' => '198501012010011001']);

        $this->assertSame(0, $repository->pegawaiPensiun($user));
        $this->assertSame(0, $repository->pegawaiAktif($user));
        $this->assertSame(0, $repository->pegawaiNonaktif($user));
        $this->assertSame(0, $repository->totalPNS($user));
        $this->assertSame(0, $repository->totalPPPK($user));
        $this->assertSame(0, $repository->naikPangkat($user));
        $this->assertSame(0, $repository->kgb($user));
        $this->assertSame(0, $repository->pensiun($user));
        $this->assertSame(['PNS' => 0, 'PPPK' => 0], $repository->chartJenisPegawai($user));
        $this->assertSame(['Laki-laki' => 0, 'Perempuan' => 0], $repository->ChartJenisKelamin($user));
        $this->assertCount(3, $repository->chartStatus($user));
        $this->assertSame([], $repository->chartPangkat($user));
        $this->assertSame([], $repository->chartGolonganPppk($user));
        $this->assertSame([], $repository->chartPendidikan($user));
        $this->assertCount(4, $repository->chartMasaKerja($user));
        $this->assertCount(4, $repository->chartUsia($user));

        $reminders = $repository->chartReminder(2026, $user);
        $history = $repository->chartRiwayat(2026, $user);

        $this->assertCount(12, $reminders['labels']);
        $this->assertSame(0, $reminders['total_event']);
        $this->assertSame(0, $reminders['pegawai_reminder']);
        $this->assertCount(12, $history['labels']);
        $this->assertSame(0, $history['total_event']);
        $this->assertSame(0, $history['pegawai_riwayat']);
    }

    public function test_dashboard_activity_and_notification_queries_handle_guest_and_user_roles(): void
    {
        $repository = new DashboardRepository();
        $user = User::factory()->create(['role' => 'user']);

        $this->assertCount(0, $repository->aktivitas());
        $this->assertCount(0, $repository->notifikasi());
        $this->assertCount(0, $repository->aktivitas($user));
        $this->assertCount(0, $repository->notifikasi($user));
        $this->assertCount(0, $repository->notifikasiPengguna($user));
        $this->assertCount(0, $repository->aktivitasPengguna($user));
    }

    public function test_available_years_returns_distinct_descending_years_with_role_scope(): void
    {
        $repository = new DashboardRepository();
        $superAdmin = new User(['role' => 'super_admin']);
        $adminA = new User(['role' => 'admin', 'kode_unit_kerja' => 'UNIT-A']);
        $adminWithoutUnit = new User(['role' => 'admin']);
        $user = new User(['role' => 'user']);

        $this->assertSame([], $repository->availableYears($superAdmin));

        Department::create(['kode' => 'UNIT-A', 'nama' => 'Unit A', 'status' => true]);
        Department::create(['kode' => 'UNIT-B', 'nama' => 'Unit B', 'status' => true]);
        Employee::create([
            'nip' => '198501012010011001',
            'nama' => 'Pegawai A1',
            'kode_unit_kerja' => 'UNIT-A',
            'status' => 'Aktif',
            'tmt_pangkat_baru' => '2024-01-01',
            'tmt_kgb_baru' => null,
            'tmt_pensiun' => null,
        ]);
        Employee::create([
            'nip' => '198501012010011002',
            'nama' => 'Pegawai A2',
            'kode_unit_kerja' => 'UNIT-A',
            'status' => 'Aktif',
            'tmt_pangkat_baru' => '2024-12-01',
            'tmt_kgb_baru' => '2025-01-01',
            'tmt_pensiun' => null,
        ]);
        Employee::create([
            'nip' => '198501012010011003',
            'nama' => 'Pegawai B1',
            'kode_unit_kerja' => 'UNIT-B',
            'status' => 'Aktif',
            'tmt_pangkat_baru' => null,
            'tmt_kgb_baru' => '2026-01-01',
            'tmt_pensiun' => null,
        ]);
        Employee::create([
            'nip' => '198501012010011004',
            'nama' => 'Pegawai B2',
            'kode_unit_kerja' => 'UNIT-B',
            'status' => 'Aktif',
            'tmt_pangkat_baru' => null,
            'tmt_kgb_baru' => null,
            'tmt_pensiun' => null,
        ]);

        $this->assertSame([2026, 2025, 2024], $repository->availableYears($superAdmin));
        $this->assertSame([2025, 2024], $repository->availableYears($adminA));
        $this->assertSame([], $repository->availableYears($adminWithoutUnit));
        $this->assertSame([], $repository->availableYears($user));
    }

    public function test_dashboard_age_and_service_charts_classify_each_boundary(): void
    {
        Carbon::setTestNow('2026-11-14 12:00:00');

        try {
            foreach ([
                ['198501012010011011', '2021-11-14', '1996-11-14'],
                ['198501012010011012', '2016-11-14', '1986-11-14'],
                ['198501012010011013', '2006-11-14', '1976-11-14'],
                ['198501012010011014', '2005-11-14', '1975-11-14'],
            ] as [$nip, $tanggalMasuk, $tanggalLahir]) {
                Employee::create([
                    'nip' => $nip,
                    'nama' => 'Pegawai ' . $nip,
                    'status' => 'Aktif',
                    'tanggal_masuk' => $tanggalMasuk,
                    'tanggal_lahir' => $tanggalLahir,
                ]);
            }

            $repository = new DashboardRepository();

            $this->assertSame([
                ['label' => '0 - 5 Tahun', 'value' => 1],
                ['label' => '6 - 10 Tahun', 'value' => 1],
                ['label' => '11 - 20 Tahun', 'value' => 1],
                ['label' => '> 20 Tahun', 'value' => 1],
            ], $repository->chartMasaKerja());
            $this->assertSame([
                ['label' => '20 - 30 Tahun', 'value' => 1],
                ['label' => '31 - 40 Tahun', 'value' => 1],
                ['label' => '41 - 50 Tahun', 'value' => 1],
                ['label' => '> 50 Tahun', 'value' => 1],
            ], $repository->chartUsia());
        } finally {
            Carbon::setTestNow();
        }
    }
}

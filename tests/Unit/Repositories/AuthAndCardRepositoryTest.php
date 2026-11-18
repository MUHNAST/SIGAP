<?php

namespace Tests\Unit\Repositories;

use App\Models\Employee;
use App\Models\Department;
use App\Models\User;
use App\Repositories\AuthRepository;
use App\Repositories\CardRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AuthAndCardRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_repository_reads_updates_and_deletes_user_devices(): void
    {
        $repository = new AuthRepository();
        $user = User::factory()->create();

        $this->assertSame($user->id, $repository->getUserById($user->id)->id);
        $this->assertNull($repository->getUserById(999999));
        $this->assertNull($repository->getDeviceByFingerprint($user->id, 'unknown'));
        $this->assertSame(0, $repository->countUserDevices($user->id));

        $device = $repository->updateOrCreateDevice($user->id, 'fingerprint-1', [
            'nama_perangkat' => 'Browser lama',
            'session_id' => 'session-1',
        ]);
        $updated = $repository->updateOrCreateDevice($user->id, 'fingerprint-1', [
            'nama_perangkat' => 'Browser baru',
            'session_id' => 'session-2',
        ]);

        $this->assertSame($device->id, $updated->id);
        $this->assertSame('Browser baru', $updated->nama_perangkat);
        $this->assertSame('session-2', $updated->session_id);
        $this->assertSame(1, $repository->countUserDevices($user->id));
        $this->assertSame(
            $updated->id,
            $repository->getDeviceByFingerprint($user->id, 'fingerprint-1')->id
        );
        $this->assertSame(1, $repository->deleteDeviceBySession('session-2'));
        $this->assertSame(0, $repository->countUserDevices($user->id));
        $this->assertSame(0, $repository->deleteDeviceBySession('missing-session'));
    }

    public function test_card_repository_filters_upcoming_agendas_by_type_and_role(): void
    {
        Carbon::setTestNow('2026-11-14 10:00:00');

        try {
            $repository = new CardRepository();
            Department::create(['kode' => 'UNIT-A', 'nama' => 'Unit A', 'status' => true]);
            Department::create(['kode' => 'UNIT-B', 'nama' => 'Unit B', 'status' => true]);
            $this->createEmployee('198501012010011001', [
                'kode_unit_kerja' => 'UNIT-A',
                'status_pegawai' => 'PNS',
                'tmt_pangkat_baru' => '2026-12-14',
                'tmt_kgb_baru' => '2026-12-14',
                'tmt_pensiun' => '2027-02-14',
            ]);
            $this->createEmployee('198501012010011002', [
                'kode_unit_kerja' => 'UNIT-A',
                'status_pegawai' => 'PPPK',
                'tmt_pangkat_baru' => '2026-12-14',
                'tmt_kgb_baru' => '2026-12-14',
                'tmt_pensiun' => '2027-02-14',
            ]);
            $this->createEmployee('198501012010011003', [
                'kode_unit_kerja' => 'UNIT-B',
                'status_pegawai' => 'PNS',
                'tmt_pangkat_baru' => '2027-05-14',
                'tmt_kgb_baru' => '2027-05-14',
                'tmt_pensiun' => '2027-05-14',
            ]);

            $admin = new User(['role' => 'admin', 'kode_unit_kerja' => 'UNIT-A']);

            $this->assertSame(
                ['198501012010011001'],
                $repository->getUpcoming('kp', $admin)->pluck('nip')->all()
            );
            $this->assertSame(
                ['198501012010011001', '198501012010011002'],
                $repository->getUpcoming('kgb', $admin)->pluck('nip')->all()
            );
            $this->assertSame(
                ['198501012010011001', '198501012010011002'],
                $repository->getUpcoming('mp', $admin)->pluck('nip')->all()
            );
            $this->assertSame(
                ['198501012010011001', '198501012010011002'],
                $repository->getUpcoming('kgb')->pluck('nip')->all()
            );
            $this->assertCount(0, $repository->getUpcoming('unsupported', $admin));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_card_repository_returns_information_for_each_supported_agenda(): void
    {
        $repository = new CardRepository();

        $this->assertSame('Kenaikan Pangkat', $repository->getTypeInformation('kp')['title']);
        $this->assertSame('Kenaikan Gaji Berkala', $repository->getTypeInformation('kgb')['title']);
        $this->assertSame('Masa Pensiun', $repository->getTypeInformation('mp')['title']);
        $this->assertSame('Data Tidak Ditemukan', $repository->getTypeInformation('unknown')['title']);
        $this->assertNull($repository->getTypeInformation('unknown')['date_field']);
    }

    private function createEmployee(string $nip, array $attributes): Employee
    {
        return Employee::create(array_merge([
            'nip' => $nip,
            'nama' => 'Pegawai Uji',
            'status' => 'Aktif',
        ], $attributes));
    }
}

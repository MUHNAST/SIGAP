<?php

namespace Tests\Unit\Services;

use App\Models\Employee;
use App\Models\Department;
use App\Models\User;
use App\Services\EmployeeUserSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EmployeeUserSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_creates_user_and_copies_employee_identity(): void
    {
        Department::create([
            'kode' => 'UNIT-01',
            'nama' => 'Unit 01',
            'status' => true,
        ]);
        $employee = new Employee([
            'nip' => '199001012020011001',
            'nama' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'no_hp' => '081234567890',
            'kode_unit_kerja' => 'UNIT-01',
            'foto' => 'employees/budi.jpg',
        ]);
        $employee->save();

        $user = (new EmployeeUserSyncService())->sync($employee);

        $this->assertSame('user', $user->role);
        $this->assertSame($employee->nip, $user->nip);
        $this->assertSame($employee->nip, $user->username);
        $this->assertSame($employee->nama, $user->nama);
        $this->assertSame($employee->email, $user->email);
        $this->assertSame($employee->no_hp, $user->no_hp);
        $this->assertSame($employee->kode_unit_kerja, $user->kode_unit_kerja);
        $this->assertSame($employee->foto, $user->foto);
        $this->assertNotEmpty($user->password);
    }

    public function test_sync_reuses_existing_account_and_preserves_its_role(): void
    {
        Employee::create([
            'nip' => '199001012020011001',
            'nama' => 'Original Name',
        ]);
        $existing = User::factory()->create([
            'role' => 'admin',
            'nip' => '199001012020011001',
        ]);
        $employee = new Employee([
            'nip' => '199001012020011001',
            'nama' => 'Updated Name',
        ]);

        $synced = (new EmployeeUserSyncService())->sync($employee);

        $this->assertSame($existing->id, $synced->id);
        $this->assertSame('admin', $synced->role);
        $this->assertSame('Updated Name', $synced->nama);
    }

    public function test_sync_finds_account_by_new_nip_when_old_nip_is_not_found(): void
    {
        Employee::create([
            'nip' => '199001012020011002',
            'nama' => 'Original Name',
        ]);
        $existing = User::factory()->create([
            'role' => 'user',
            'nip' => '199001012020011002',
        ]);
        $employee = new Employee([
            'nip' => '199001012020011002',
            'nama' => 'Updated Name',
        ]);

        $synced = (new EmployeeUserSyncService())->sync(
            $employee,
            '199001012020011001'
        );

        $this->assertSame($existing->id, $synced->id);
        $this->assertSame($employee->nip, $synced->nip);
    }

    public function test_sync_rejects_email_or_phone_owned_by_another_user(): void
    {
        User::factory()->create([
            'email' => 'taken@example.com',
            'no_hp' => '081111111111',
        ]);
        $service = new EmployeeUserSyncService();

        try {
            $service->sync(new Employee([
                'nip' => '199001012020011003',
                'nama' => 'New Employee',
                'email' => 'taken@example.com',
            ]));
            $this->fail('An email owned by another user must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('email', $exception->errors());
        }

        try {
            $service->sync(new Employee([
                'nip' => '199001012020011004',
                'nama' => 'New Employee',
                'no_hp' => '081111111111',
            ]));
            $this->fail('A phone owned by another user must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('no_hp', $exception->errors());
        }
    }
}

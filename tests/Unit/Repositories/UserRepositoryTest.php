<?php

namespace Tests\Unit\Repositories;

use App\Models\Department;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository();
    }

    public function test_user_reads_are_scoped_for_super_admin_and_admin_actors(): void
    {
        Department::create(['kode' => 'UNIT-A', 'nama' => 'Unit A', 'status' => true]);
        Department::create(['kode' => 'UNIT-B', 'nama' => 'Unit B', 'status' => true]);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $adminA = User::factory()->create(['role' => 'admin', 'kode_unit_kerja' => 'UNIT-A']);
        $userA = User::factory()->create(['role' => 'user', 'kode_unit_kerja' => 'UNIT-A']);
        $userB = User::factory()->create(['role' => 'user', 'kode_unit_kerja' => 'UNIT-B']);

        $this->assertEqualsCanonicalizing(
            [$superAdmin->id, $adminA->id, $userA->id, $userB->id],
            $this->repository->getAll($superAdmin)->pluck('id')->all()
        );
        $this->assertSame([$userA->id], $this->repository->getAll($adminA)->pluck('id')->all());
        $this->assertSame($userA->id, $this->repository->getById($userA->id, $adminA)->id);
        $this->assertSame($userB->id, $this->repository->getById($userB->id, $superAdmin)->id);
        $this->assertNull($this->repository->getByUsername('not-found'));
        $this->assertSame($userA->id, $this->repository->getByUsername($userA->username)->id);

        $adminWithoutUnit = User::factory()->create(['role' => 'admin', 'kode_unit_kerja' => null]);
        $ordinaryUser = User::factory()->create(['role' => 'user']);
        $this->assertCount(0, $this->repository->getAll($adminWithoutUnit));
        $this->assertCount(0, $this->repository->getAll($ordinaryUser));

        $this->expectException(ModelNotFoundException::class);
        $this->repository->getById($userB->id, $adminA);
    }

    public function test_user_creation_and_update_store_new_photo_and_remove_replaced_photo(): void
    {
        Storage::fake('public');
        $user = $this->repository->create($this->userData('photo-user'));
        Storage::disk('public')->put('pengguna/old.jpg', 'old image');
        $user->update(['foto' => 'pengguna/old.jpg']);

        $updated = $this->repository->update($user, [
            'nama' => 'Updated user',
            'foto' => UploadedFile::fake()->create('avatar.jpg', 20, 'image/jpeg'),
        ]);

        $this->assertSame('Updated user', $updated->nama);
        $this->assertNotSame('pengguna/old.jpg', $updated->foto);
        Storage::disk('public')->assertExists($updated->foto);
        Storage::disk('public')->assertMissing('pengguna/old.jpg');
    }

    public function test_user_photo_is_cleaned_up_when_create_or_update_fails(): void
    {
        Storage::fake('public');
        $this->repository->create($this->userData('duplicate-user'));
        $this->expectException(QueryException::class);

        try {
            $this->repository->create(array_merge(
                $this->userData('duplicate-user'),
                ['foto' => UploadedFile::fake()->create('duplicate.jpg', 20, 'image/jpeg')]
            ));
        } finally {
            $this->assertSame([], Storage::disk('public')->allFiles('pengguna'));
        }
    }

    public function test_user_photo_is_cleaned_when_update_fails_without_removing_existing_photo(): void
    {
        Storage::fake('public');
        $first = $this->repository->create($this->userData('update-user-a'));
        $second = $this->repository->create($this->userData('update-user-b'));
        Storage::disk('public')->put('pengguna/existing.jpg', 'existing image');
        $first->update(['foto' => 'pengguna/existing.jpg']);

        try {
            $this->repository->update($first, [
                'username' => $second->username,
                'foto' => UploadedFile::fake()->create('replacement.jpg', 20, 'image/jpeg'),
            ]);
            $this->fail('A duplicate username must fail the database update.');
        } catch (QueryException) {
            Storage::disk('public')->assertExists('pengguna/existing.jpg');
            $this->assertSame(['pengguna/existing.jpg'], Storage::disk('public')->allFiles('pengguna'));
        }
    }

    public function test_delete_removes_user_and_non_default_photo_only_after_success(): void
    {
        Storage::fake('public');
        $user = $this->repository->create($this->userData('delete-user'));
        Storage::disk('public')->put('pengguna/delete.jpg', 'image');
        $user->update(['foto' => 'pengguna/delete.jpg']);

        $this->assertTrue($this->repository->delete($user));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        Storage::disk('public')->assertMissing('pengguna/delete.jpg');

        $defaultPhotoUser = $this->repository->create(array_merge(
            $this->userData('default-photo-user'),
            ['foto' => 'images/default-user.png']
        ));
        $this->assertTrue($this->repository->delete($defaultPhotoUser));
    }

    public function test_privileged_unit_and_available_department_queries_honor_exclusions(): void
    {
        $unitA = Department::create(['kode' => 'UNIT-A', 'nama' => 'Unit A', 'status' => true]);
        Department::create(['kode' => 'UNIT-B', 'nama' => 'Unit B', 'status' => true]);
        Department::create(['kode' => 'UNIT-C', 'nama' => 'Unit C', 'status' => false]);
        $adminA = User::factory()->create(['role' => 'admin', 'kode_unit_kerja' => $unitA->kode]);
        $superAdminB = User::factory()->create(['role' => 'super_admin', 'kode_unit_kerja' => 'UNIT-B']);

        $this->assertTrue($this->repository->isUnitUsedByPrivilegedRole('UNIT-A'));
        $this->assertTrue($this->repository->isUnitUsedByPrivilegedRole('UNIT-A', $superAdminB->id));
        $this->assertFalse($this->repository->isUnitUsedByPrivilegedRole('UNIT-A', $adminA->id));
        $this->assertFalse($this->repository->isUnitUsedByPrivilegedRole('UNIT-C'));

        $available = $this->repository->getAvailableDepartments();
        $this->assertSame([], $available->pluck('kode')->all());
        $availableIgnoringAdmin = $this->repository->getAvailableDepartments($adminA->id);
        $this->assertSame(['UNIT-A'], $availableIgnoringAdmin->pluck('kode')->all());
    }

    private function userData(string $username): array
    {
        return [
            'username' => $username,
            'email' => "{$username}@example.test",
            'role' => 'user',
            'password' => 'password-123',
        ];
    }
}

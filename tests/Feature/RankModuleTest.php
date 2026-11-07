<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Permission;
use App\Models\Rank;
use App\Models\RolePermission;
use App\Models\User;
use App\Repositories\RankRepository;
use App\Services\RankService;
use Database\Seeders\RankSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_list_create_and_update_rank_data(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($user)->get(route('rank.index'))
            ->assertOk()
            ->assertViewIs('rank.index');

        $this->post(route('rank.store'), [
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ])->assertRedirect(route('rank.index'));

        $rank = Rank::where('kode', 'III/a')->firstOrFail();
        $this->put(route('rank.update', $rank), [
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ])->assertRedirect(route('rank.index'));

        $this->put(route('rank.update', $rank), [
            'kode' => 'III/b',
            'nama' => 'Penata Muda Tingkat I',
            'urutan_pangkat' => 10,
        ])->assertRedirect(route('rank.index'));

        $this->assertDatabaseHas('ranks', [
            'id' => $rank->id,
            'kode' => 'III/b',
            'nama' => 'Penata Muda Tingkat I',
            'urutan_pangkat' => 10,
        ]);
    }

    public function test_rank_validation_rejects_duplicate_data_and_invalid_order(): void
    {
        $this->seed(RankSeeder::class);
        $user = User::factory()->create(['role' => 'super_admin']);
        $count = Rank::count();

        $this->actingAs($user)->post(route('rank.store'), [
            'kode' => 'III/a',
            'nama' => 'Pangkat Duplikat',
            'urutan_pangkat' => 20,
        ])->assertSessionHasErrors('kode');

        $this->post(route('rank.store'), [
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ])->assertSessionHasErrors(['kode', 'nama', 'urutan_pangkat']);

        $this->post(route('rank.store'), [
            'kode' => 'III/b',
            'nama' => 'Pangkat Tidak Valid',
            'urutan_pangkat' => 0,
        ])->assertSessionHasErrors('urutan_pangkat');

        $this->assertDatabaseCount('ranks', $count);
    }

    public function test_rank_index_permission_allows_authorized_admin_and_denies_unauthorized_admin(): void
    {
        Department::create(['kode' => 'UNIT-A', 'nama' => 'Unit A', 'status' => true]);
        $permission = Permission::create([
            'kode' => 'pangkat.view',
            'nama' => 'Lihat Pangkat',
            'modul' => 'pangkat',
        ]);
        $createPermission = Permission::create([
            'kode' => 'pangkat.create',
            'nama' => 'Tambah Pangkat',
            'modul' => 'pangkat',
        ]);
        $updatePermission = Permission::create([
            'kode' => 'pangkat.update',
            'nama' => 'Ubah Pangkat',
            'modul' => 'pangkat',
        ]);
        RolePermission::create(['role' => 'admin', 'permission_id' => $permission->id]);
        RolePermission::create(['role' => 'admin', 'permission_id' => $createPermission->id]);
        RolePermission::create(['role' => 'admin', 'permission_id' => $updatePermission->id]);
        $existingRank = Rank::create([
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ]);

        $authorized = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'UNIT-A',
        ]);
        $this->actingAs($authorized)->get(route('rank.index'))->assertOk();
        $this->post(route('rank.store'), [
            'kode' => 'IV/e',
            'nama' => 'Pembina Utama',
            'urutan_pangkat' => 17,
        ])->assertRedirect(route('rank.index'));
        $this->put(route('rank.update', $existingRank), [
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ])->assertRedirect(route('rank.index'));

        RolePermission::query()->delete();
        $unauthorized = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'UNIT-A',
        ]);
        $this->actingAs($unauthorized)->get(route('rank.index'))->assertForbidden();
        $this->post(route('rank.store'), [
            'kode' => 'I/a',
            'nama' => 'Juru Muda',
            'urutan_pangkat' => 1,
        ])->assertForbidden();
        $this->put(route('rank.update', $existingRank), [
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ])->assertForbidden();
    }

    public function test_rank_service_lookup_returns_existing_record_and_throws_for_missing_id(): void
    {
        $rank = Rank::create([
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ]);
        $service = new RankService(new RankRepository());

        $this->assertSame($rank->id, $service->getById($rank->id)->id);
        $this->expectException(ModelNotFoundException::class);
        $service->getById(99999);
    }
}

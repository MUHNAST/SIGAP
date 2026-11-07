<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use App\Repositories\ActivityLogRepository;
use App\Repositories\DashboardRepository;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ActivityLogWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createDepartment(string $code): Department
    {
        return Department::create([
            'kode' => $code,
            'nama' => 'Unit '.$code,
            'status' => true,
        ]);
    }

    private function createUser(string $role, ?string $unit, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'kode_unit_kerja' => $unit,
        ], $attributes));
    }

    private function createLog(User $user, string $activity, string $description, string $createdAt): ActivityLog
    {
        return ActivityLog::create([
            'id_user' => $user->id,
            'aktivitas' => $activity,
            'deskripsi' => $description,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    public function test_log_persists_authenticated_actor_description_and_request_metadata_once(): void
    {
        $department = $this->createDepartment('LOG-A');
        $user = $this->createUser('admin', $department->kode);

        $this->actingAs($user);
        $this->app->instance('request', Request::create(
            '/',
            'GET',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '192.0.2.10',
                'HTTP_USER_AGENT' => 'SIGAP test browser',
            ]
        ));

        $service = app(ActivityLogService::class);
        $service->log('Update Data Pegawai', 'Mengubah data pegawai uji.');

        $this->assertDatabaseCount('activity_logs', 1);
        $log = ActivityLog::sole();
        $this->assertSame($user->id, $log->id_user);
        $this->assertSame('Update Data Pegawai', $log->aktivitas);
        $this->assertSame('Mengubah data pegawai uji.', $log->deskripsi);
        $this->assertSame('192.0.2.10', $log->ip_address);
        $this->assertSame('SIGAP test browser', $log->user_agent);
    }

    public function test_activity_log_repository_returns_filtered_ordered_rows_and_statistics(): void
    {
        Carbon::setTestNow('2026-11-04 12:00:00');
        $department = $this->createDepartment('LOG-REPOSITORY');
        $user = $this->createUser('user', $department->kode);
        $anotherUser = $this->createUser('user', $department->kode);
        $yesterday = $this->createLog(
            $user,
            'Login',
            'Aktivitas kemarin',
            Carbon::yesterday()->toDateTimeString()
        );
        $today = $this->createLog(
            $user,
            'Tambah Data Pegawai',
            'Aktivitas hari ini',
            Carbon::today()->toDateTimeString()
        );
        $otherToday = $this->createLog(
            $anotherUser,
            'Update Data Pegawai',
            'Update hari ini',
            Carbon::today()->addHour()->toDateTimeString()
        );
        $repository = new ActivityLogRepository(new ActivityLog());

        $this->assertSame(
            [$otherToday->id, $today->id, $yesterday->id],
            $repository->getAll(['id', 'id_user', 'aktivitas'])
                ->pluck('id')
                ->all()
        );
        $this->assertSame(
            [$today->id, $yesterday->id],
            $repository->getByUserId($user->id)->pluck('id')->all()
        );
        $this->assertSame(
            ['Login', 'Tambah Data Pegawai', 'Update Data Pegawai'],
            $repository->getDistinctActivities()->all()
        );
        $this->assertSame(3, $repository->countAll());
        $this->assertSame(2, $repository->countToday());
        $this->assertSame(2, $repository->countActiveUsersToday());
        $this->assertSame('3', $repository->countByDepartment($department->kode));
        $this->assertSame('2', $repository->countTodayByDepartment($department->kode));
        $this->assertSame(
            '2',
            $repository->countActiveUsersTodayByDepartment($department->kode)
        );
        $this->assertSame(2, $repository->countPerubahanData());
        $this->assertSame(
            '2',
            $repository->countPerubahanDataByDepartment($department->kode)
        );
    }

    public function test_successful_login_and_logout_are_logged_but_failed_login_is_not(): void
    {
        $department = $this->createDepartment('AUTH-LOG');
        $user = User::create([
            'username' => 'activity-log-auth',
            'email' => 'activity-log-auth@example.test',
            'nama' => 'Pengguna Log',
            'role' => 'admin',
            'kode_unit_kerja' => $department->kode,
            'password' => bcrypt('password-123'),
        ]);

        $this->post(route('login.process'), [
            'username' => $user->username,
            'password' => 'incorrect',
        ]);
        $this->assertDatabaseCount('activity_logs', 0);

        $this->post(route('login.process'), [
            'username' => $user->username,
            'password' => 'password-123',
        ])->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('activity_logs', [
            'id_user' => $user->id,
            'aktivitas' => 'Login',
        ]);
        $this->assertDatabaseCount('activity_logs', 1);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertDatabaseHas('activity_logs', [
            'id_user' => $user->id,
            'aktivitas' => 'Logout',
        ]);
        $this->assertDatabaseCount('activity_logs', 2);
    }

    public function test_department_create_and_update_are_logged_once_and_validation_failure_is_not(): void
    {
        $adminDepartment = $this->createDepartment('LOG-ADMIN');
        $targetDepartment = $this->createDepartment('LOG-TGT');
        $admin = $this->createUser('admin', $adminDepartment->kode);
        foreach (['unit_kerja.create', 'unit_kerja.update'] as $permissionCode) {
            $permission = Permission::create([
                'kode' => $permissionCode,
                'nama' => $permissionCode,
                'modul' => 'unit_kerja',
            ]);
            RolePermission::create([
                'role' => 'admin',
                'permission_id' => $permission->id,
            ]);
        }

        $this->actingAs($admin)
            ->post(route('department.store'), [
                'kode' => 'LOG-NEW',
                'nama' => 'Unit Baru',
                'status' => true,
            ])
            ->assertRedirect(route('department.index'));
        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertDatabaseHas('activity_logs', [
            'id_user' => $admin->id,
            'aktivitas' => 'Tambah Data Unit Kerja',
        ]);

        $updateResponse = $this->from(route('department.index'))
            ->put(route('department.update', $targetDepartment->kode), [
                'kode' => $targetDepartment->kode,
                'nama' => 'Unit Target Diperbarui',
                'status' => true,
            ]);
        $this->assertSame(
            302,
            $updateResponse->getStatusCode(),
            $updateResponse->getContent()
        );
        $this->assertDatabaseCount('activity_logs', 2);
        $this->assertDatabaseHas('activity_logs', [
            'id_user' => $admin->id,
            'aktivitas' => 'Update Data Unit Kerja',
        ]);

        $validationResponse = $this->from(route('department.index'))
            ->post(route('department.store'), [])
            ->assertRedirect(route('department.index'));
        $this->assertNotEmpty($validationResponse->getSession()->get('errors'));
        $this->assertDatabaseCount('activity_logs', 2);
    }

    public function test_activity_log_index_is_scoped_filtered_sorted_and_paginated(): void
    {
        Carbon::setTestNow('2026-11-04 12:00:00');
        config(['app.pagination' => 2]);
        $first = $this->createDepartment('LOG-UNIT-A');
        $second = $this->createDepartment('LOG-UNIT-B');
        $admin = $this->createUser('admin', $first->kode);
        $firstUser = $this->createUser('user', $first->kode, [
            'nama' => 'Pegawai Unit A',
        ]);
        $otherUser = $this->createUser('user', $second->kode, [
            'nama' => 'Pegawai Unit B Rahasia',
        ]);

        $oldest = $this->createLog(
            $firstUser,
            'Login',
            'Login lama',
            Carbon::yesterday()->setTime(8, 0)->toDateTimeString()
        );
        $today = $this->createLog(
            $firstUser,
            'Tambah Data Pegawai',
            'Menambah pegawai uji',
            Carbon::today()->setTime(10, 0)->toDateTimeString()
        );
        $newest = $this->createLog(
            $firstUser,
            'Update Data Pegawai',
            'Memperbarui pegawai uji',
            Carbon::today()->setTime(11, 0)->toDateTimeString()
        );
        $this->createLog(
            $otherUser,
            'Login',
            'Aktivitas unit lain rahasia',
            Carbon::today()->setTime(11, 30)->toDateTimeString()
        );

        $response = $this->actingAs($admin)
            ->get(route('activityLog.index'));

        $response->assertOk()
            ->assertViewIs('activityLog.index')
            ->assertViewHas('totalAktivitas', fn ($total) => (int) $total === 3)
            ->assertViewHas('aktivitasHariIni', fn ($total) => (int) $total === 2)
            ->assertViewHas('penggunaAktifHariIni', fn ($total) => (int) $total === 1)
            ->assertViewHas('data', function ($data) use ($newest, $today) {
                return $data->total() === 3
                    && $data->items()[0]->id === $newest->id
                    && $data->items()[1]->id === $today->id;
            })
            ->assertDontSee('Aktivitas unit lain rahasia');

        $this->get(route('activityLog.index', [
            'search' => 'Memperbarui',
            'tanggal' => Carbon::today()->toDateString(),
        ]))
            ->assertOk()
            ->assertViewHas('data', function ($data) use ($newest) {
                return $data->total() === 1
                    && $data->sole()->id === $newest->id;
            });

        $superAdmin = $this->createUser('super_admin', null);
        $this->actingAs($superAdmin)
            ->get(route('activityLog.index'))
            ->assertOk()
            ->assertViewHas('totalAktivitas', fn ($total) => (int) $total === 4)
            ->assertViewHas('data', fn ($data) => $data->total() === 4);

        $this->actingAs($admin);
        $this->get(route('activityLog.index', ['page' => 2]))
            ->assertOk()
            ->assertViewHas('data', function ($data) use ($oldest) {
                return $data->currentPage() === 2
                    && $data->items()[0]->id === $oldest->id;
            });
    }

    public function test_activity_filter_categories_match_logged_activity_prefixes(): void
    {
        $department = $this->createDepartment('LOG-FILTER');
        $admin = $this->createUser('admin', $department->kode);
        $user = $this->createUser('user', $department->kode);
        $matching = $this->createLog(
            $user,
            'Tambah Data Pegawai',
            'Menambah pegawai',
            now()->toDateTimeString()
        );
        $this->createLog(
            $user,
            'Login',
            'Login',
            now()->toDateTimeString()
        );

        $this->actingAs($admin)
            ->get(route('activityLog.index', ['aktivitas' => 'Tambah Data']))
            ->assertOk()
            ->assertViewHas('activities', function ($activities) {
                return $activities->contains('Tambah Data');
            })
            ->assertViewHas('data', function ($data) use ($matching) {
                return $data->total() === 1
                    && $data->sole()->id === $matching->id;
            });
    }

    public function test_activity_log_show_route_displays_the_selected_activity(): void
    {
        $department = $this->createDepartment('LOG-SHOW-A');
        $otherDepartment = $this->createDepartment('LOG-SHOW-B');
        $admin = $this->createUser('admin', $department->kode);
        $superAdmin = $this->createUser('super_admin', null);
        $actor = $this->createUser('user', $department->kode);
        $otherActor = $this->createUser('user', $otherDepartment->kode);
        $log = $this->createLog(
            $actor,
            'Login',
            'Aktivitas detail uji',
            now()->toDateTimeString()
        );
        $otherLog = $this->createLog(
            $otherActor,
            'Login',
            'Aktivitas unit lain',
            now()->toDateTimeString()
        );
        $orphanedLog = ActivityLog::create([
            'id_user' => null,
            'aktivitas' => 'Login',
            'deskripsi' => 'Aktivitas tanpa actor',
        ]);

        $this->actingAs($admin)
            ->get(route('activityLog.show', $log->id))
            ->assertOk()
            ->assertViewIs('activityLog.detail')
            ->assertViewHas('activityLog', fn ($activity) => $activity->is($log))
            ->assertSee('Aktivitas detail uji');

        $this->get(route('activityLog.show', $otherLog->id))->assertNotFound();

        $this->actingAs($superAdmin)
            ->get(route('activityLog.show', $otherLog->id))
            ->assertOk()
            ->assertSee('Aktivitas unit lain');

        $this->get(route('activityLog.show', $orphanedLog->id))
            ->assertOk()
            ->assertSee('Pengguna tidak diketahui');

        $this->actingAs($actor)
            ->get(route('activityLog.show', $log->id))
            ->assertForbidden();
    }

    public function test_dashboard_activity_feed_is_latest_scoped_and_empty_without_user(): void
    {
        Carbon::setTestNow('2026-11-04 12:00:00');
        $firstDepartment = $this->createDepartment('LOG-DASH-A');
        $secondDepartment = $this->createDepartment('LOG-DASH-B');
        $admin = $this->createUser('admin', $firstDepartment->kode);
        $superAdmin = $this->createUser('super_admin', null);
        $firstUser = $this->createUser('user', $firstDepartment->kode);
        $secondUser = $this->createUser('user', $secondDepartment->kode);

        foreach (range(1, 7) as $index) {
            $this->createLog(
                $firstUser,
                'Login '.$index,
                'Aktivitas '.$index,
                Carbon::today()->addMinutes($index)->toDateTimeString()
            );
        }
        $outsideUnit = $this->createLog(
            $secondUser,
            'Login Unit B',
            'Tidak boleh muncul pada feed admin',
            Carbon::today()->addMinutes(20)->toDateTimeString()
        );

        $repository = new DashboardRepository();
        $this->assertCount(0, $repository->aktivitas());
        $this->assertCount(6, $repository->aktivitas($admin));
        $this->assertSame(
            'Aktivitas 7',
            $repository->aktivitas($admin)->first()->deskripsi
        );
        $this->assertFalse(
            $repository->aktivitas($admin)->contains('id', $outsideUnit->id)
        );
        $this->assertTrue(
            $repository->aktivitas($superAdmin)->contains('id', $outsideUnit->id)
        );
    }

    public function test_activity_log_index_displays_actor_role_and_employee_nip(): void
    {
        $department = $this->createDepartment('LOG-VIEW');
        $userWithoutNip = $this->createUser('user', $department->kode, [
            'nama' => 'Aktor Tanpa NIP',
        ]);
        $this->createLog(
            $userWithoutNip,
            'Login',
            'Aktor tanpa NIP',
            now()->subMinute()->toDateTimeString()
        );
        $employee = Employee::create([
            'nip' => '199001012020011112',
            'nama' => 'Pegawai Log View',
            'kode_unit_kerja' => $department->kode,
            'status_pegawai' => 'PNS',
        ]);
        $admin = $this->createUser('admin', $department->kode);
        $actor = $this->createUser('user', $department->kode, [
            'nama' => 'Aktor Log',
            'nip' => $employee->nip,
        ]);
        $actorLog = $this->createLog(
            $actor,
            'Login',
            'Aktor login',
            now()->toDateTimeString()
        );

        $this->actingAs($admin)
            ->get(route('activityLog.index'))
            ->assertOk()
            ->assertSee('Aktor Log')
            ->assertSee('Aktor Tanpa NIP')
            ->assertSee('NIP: '.$employee->nip)
            ->assertViewHas('data', function ($data) use ($employee, $actorLog) {
                $actor = $data->firstWhere('id', $actorLog->id)->user;

                return $actor->role === 'user'
                    && $actor->nip === $employee->nip;
            });
    }

    public function test_user_can_only_view_own_logs_and_staff_cannot_view_other_routes(): void
    {
        $department = $this->createDepartment('LOG-USER');
        $ownEmployee = Employee::create([
            'nip' => '199001012020011113',
            'nama' => 'Pegawai User Sendiri',
            'kode_unit_kerja' => $department->kode,
            'status_pegawai' => 'PNS',
        ]);
        $own = $this->createUser('user', $department->kode, [
            'nama' => 'User Sendiri',
            'nip' => $ownEmployee->nip,
        ]);
        $other = $this->createUser('user', $department->kode, ['nama' => 'User Lain']);
        $this->createLog($own, 'Login', 'Aktivitas saya', now()->toDateTimeString());
        $this->createLog($other, 'Login', 'Aktivitas bukan saya', now()->toDateTimeString());

        $response = $this->actingAs($own)
            ->get(route('activityLog.user', [
                'search' => 'Aktivitas saya',
                'tanggal' => today()->toDateString(),
            ]));

        $response->assertOk()
            ->assertViewIs('activityLog.show')
            ->assertViewHas('data', function ($data) {
                return $data->total() === 1
                    && $data->sole()->deskripsi === 'Aktivitas saya';
            })
            ->assertSee('Aktivitas saya')
            ->assertDontSee('Aktivitas bukan saya');

        $this->get(route('activityLog.index'))->assertForbidden();
    }

    public function test_activity_log_requires_authentication_and_admin_unit(): void
    {
        $this->get(route('activityLog.index'))->assertRedirect(route('login'));

        $adminWithoutUnit = $this->createUser('admin', null);
        $this->actingAs($adminWithoutUnit)
            ->get(route('activityLog.index'))
            ->assertForbidden();
    }
}

<?php

namespace Tests\Unit\Services;

use App\Models\Employee;
use App\Models\User;
use App\Repositories\SettingRepository;
use App\Services\EmployeeService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SettingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_photo_returns_existing_user_when_no_upload_is_present(): void
    {
        $user = User::factory()->create();
        $repository = Mockery::mock(SettingRepository::class);
        $repository->shouldReceive('findUserById')
            ->once()
            ->with($user->id)
            ->andReturn($user);
        $service = new SettingService(
            $repository,
            Mockery::mock(EmployeeService::class)
        );
        $this->actingAs($user);

        $this->assertSame(
            $user,
            $service->updatePhoto(Request::create('/', 'POST'))
        );
    }

    public function test_update_photo_removes_new_file_and_rethrows_database_failure(): void
    {
        $user = User::factory()->create();
        $repository = Mockery::mock(SettingRepository::class);
        $file = UploadedFile::fake()->image('profile.jpg');
        $repository->shouldReceive('findUserById')
            ->once()
            ->with($user->id)
            ->andReturn($user);
        $repository->shouldReceive('storePhoto')
            ->once()
            ->with($file)
            ->andReturn('profiles/new-photo.jpg');
        $repository->shouldReceive('updateUser')
            ->once()
            ->with($user, ['foto' => 'profiles/new-photo.jpg'])
            ->andThrow(new RuntimeException('database update failed'));
        $repository->shouldReceive('deletePhoto')
            ->once()
            ->with('profiles/new-photo.jpg');
        $service = new SettingService(
            $repository,
            Mockery::mock(EmployeeService::class)
        );
        $this->actingAs($user);
        $request = Request::create('/', 'POST', [], [], ['foto' => $file]);

        try {
            $service->updatePhoto($request);
            $this->fail('A failed database update should be rethrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('database update failed', $exception->getMessage());
        }
    }

    public function test_employee_settings_methods_delegate_to_employee_service(): void
    {
        $employee = new Employee(['nip' => '199001012020011001']);
        $user = new User(['role' => 'user']);
        $data = ['nama' => 'Nama Baru'];
        $masterData = ['positions' => collect()];
        $employeeService = Mockery::mock(EmployeeService::class);
        $employeeService->shouldReceive('update')
            ->once()
            ->with($employee, $data)
            ->andReturn($employee);
        $employeeService->shouldReceive('getMasterData')
            ->once()
            ->with($user)
            ->andReturn($masterData);
        $service = new SettingService(
            Mockery::mock(SettingRepository::class),
            $employeeService
        );

        $this->assertSame($employee, $service->updateEmployee($employee, $data));
        $this->assertSame($masterData, $service->getMasterData($user));
    }
}

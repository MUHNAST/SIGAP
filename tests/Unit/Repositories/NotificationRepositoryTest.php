<?php

namespace Tests\Unit\Repositories;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Notification;
use App\Repositories\NotificationRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class NotificationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private NotificationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new NotificationRepository();
    }

    public function test_get_all_returns_a_paginated_collection_with_employee_relations(): void
    {
        $emptyPage = $this->repository->getAll();

        $this->assertInstanceOf(LengthAwarePaginator::class, $emptyPage);
        $this->assertSame(0, $emptyPage->total());

        $employeeA = $this->createEmployee('198501012010011000', 'UNIT-A');
        $employeeB = $this->createEmployee('198501012010011009', 'UNIT-B');
        $notificationA = $this->createNotification($employeeA, 'KGB', 'KGB Pegawai A');
        $notificationB = $this->createNotification($employeeB, 'Pangkat', 'Pangkat Pegawai B');

        $page = $this->repository->getAll();

        $this->assertInstanceOf(LengthAwarePaginator::class, $page);
        $this->assertSame(2, $page->total());
        $this->assertEqualsCanonicalizing(
            [$notificationA->id, $notificationB->id],
            $page->getCollection()->pluck('id')->all()
        );
        $this->assertSame($employeeA->nip, $page->getCollection()
            ->firstWhere('id', $notificationA->id)->employee->nip);
        $this->assertSame('UNIT-B', $page->getCollection()
            ->firstWhere('id', $notificationB->id)->employee->department->kode);
    }

    public function test_notification_reads_and_filters_respect_employee_and_unit_scope(): void
    {
        $employeeA = $this->createEmployee('198501012010011001', 'UNIT-A');
        $employeeB = $this->createEmployee('198501012010011002', 'UNIT-B');
        $kgbA = $this->createNotification($employeeA, 'KGB', 'KGB Pegawai A', false);
        $pangkatA = $this->createNotification($employeeA, 'Pangkat', 'Pangkat Pegawai A', true);
        $kgbB = $this->createNotification($employeeB, 'KGB', 'KGB Pegawai B', false);

        $this->assertEqualsCanonicalizing(
            [$kgbA->id, $pangkatA->id],
            $this->repository->getByEmployee($employeeA->nip)->getCollection()->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$kgbA->id, $pangkatA->id],
            $this->repository->getByUnitKerja('UNIT-A')->getCollection()->pluck('id')->all()
        );
        $this->assertSame($kgbA->id, $this->repository->getMyNotificationById($kgbA->id, $employeeA->nip)->id);
        $this->assertSame($kgbA->id, $this->repository->getById($kgbA->id)->id);
        $this->assertSame([$kgbA->id, $pangkatA->id], $this->repository->getLatest($employeeA->nip)->pluck('id')->all());
        $this->assertEqualsCanonicalizing(
            [$kgbA->id, $pangkatA->id],
            $this->repository->getLatestByUnitKerja('UNIT-A')->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing([$kgbA->id, $pangkatA->id], $this->repository->getByNip($employeeA->nip)->pluck('id')->all());
        $this->assertSame([$kgbA->id], $this->repository->getLatestByNip($employeeA->nip, 1)->pluck('id')->all());
        $this->assertSame($kgbA->id, $this->repository->getByUnitKerjaNotification($kgbA->id, 'UNIT-A')->id);
        $this->assertNull($this->repository->getByUnitKerjaNotification($kgbA->id, 'UNIT-B'));
        $this->assertSame($kgbA->id, $this->repository->getByNipNotification($kgbA->id, $employeeA->nip)->id);
        $this->assertNull($this->repository->getByNipNotification($kgbA->id, $employeeB->nip));
        $this->assertEqualsCanonicalizing(
            [$kgbA->id],
            $this->repository->getFiltered([
                'search' => 'Pegawai A',
                'kategori' => 'KGB',
                'sudah_dibaca' => '0',
            ], $employeeA->nip)->getCollection()->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$kgbA->id],
            $this->repository->getFilteredByUnitKerja([
                'search' => 'Pegawai A',
                'kategori' => 'KGB',
                'sudah_dibaca' => '0',
            ], 'UNIT-A')->getCollection()->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$kgbA->id],
            $this->repository->getFilteredByNip([
                'search' => 'Pegawai A',
                'kategori' => 'KGB',
                'sudah_dibaca' => '0',
            ], $employeeA->nip)->getCollection()->pluck('id')->all()
        );
        $this->assertSame($kgbB->id, $this->repository->getLatest($employeeB->nip)->sole()->id);
        $this->assertSame(3, $this->repository->getLatest()->count());
        $this->assertSame(3, $this->repository->getFiltered()->total());
        $this->assertSame(2, $this->repository->getFilteredByUnitKerja([], 'UNIT-A')->total());
        $this->assertSame(2, $this->repository->getFilteredByNip([], $employeeA->nip)->total());
    }

    public function test_notification_read_counts_and_duplicate_checks_track_requested_scope(): void
    {
        $employeeA = $this->createEmployee('198501012010011003', 'UNIT-A');
        $employeeB = $this->createEmployee('198501012010011004', 'UNIT-B');
        $kgbA = $this->createNotification($employeeA, 'KGB', 'KGB A', false);
        $this->createNotification($employeeA, 'Pangkat', 'Pangkat A', true);
        $kgbB = $this->createNotification($employeeB, 'KGB', 'KGB B', false);

        $referenceDate = Carbon::parse($kgbA->tanggal_referensi);
        $this->assertTrue($this->repository->existsNotification($employeeA->nip, 'KGB', $referenceDate));
        $this->assertFalse($this->repository->existsNotification($employeeA->nip, 'Pensiun', $referenceDate));
        $this->assertSame(2, $this->repository->countUnread());
        $this->assertSame(1, $this->repository->countUnread($employeeA->nip));
        $this->assertSame(3, $this->repository->countAll());
        $this->assertSame(2, $this->repository->countAll($employeeA->nip));
        $this->assertSame(2, $this->repository->countByUnitKerja('UNIT-A'));
        $this->assertSame(1, $this->repository->countUnreadByUnitKerja('UNIT-A'));
        $this->assertSame(2, $this->repository->countByNip($employeeA->nip));
        $this->assertSame(1, $this->repository->countUnreadByNip($employeeA->nip));

        $this->assertSame(2, $this->repository->markAllAsReadByUnitKerja('UNIT-A'));
        $this->assertTrue((bool) $this->repository->markAsRead($kgbA->id)->fresh()->sudah_dibaca);
        $this->assertSame(1, $this->repository->markAllAsReadByNip($employeeB->nip));
        $this->assertSame(0, $this->repository->countUnread());
        $this->assertSame(2, $this->repository->markAllAsRead($employeeA->nip));

        $this->assertSame('KGB', $this->repository->create([
            'nip' => $employeeB->nip,
            'jenis' => 'KGB',
            'judul' => 'KGB baru',
            'pesan' => 'Pesan baru',
            'tanggal_notifikasi' => today()->toDateString(),
            'tanggal_referensi' => today()->addMonths(2)->toDateString(),
        ])->jenis);
    }

    public function test_notification_schedule_queries_find_next_dates_and_return_null_when_absent(): void
    {
        $employeeA = $this->createEmployee('198501012010011005', 'UNIT-A', [
            'tmt_pangkat_baru' => '2026-12-01',
            'tmt_kgb_baru' => '2026-12-02',
            'tmt_pensiun' => '2027-01-01',
        ]);
        $employeeB = $this->createEmployee('198501012010011006', 'UNIT-B');
        $pangkat = $this->createNotification($employeeA, 'Pangkat', 'Pangkat A');
        $kgb = $this->createNotification($employeeA, 'KGB', 'KGB A');
        $retirement = $this->createNotification($employeeA, 'Pensiun', 'Pensiun A');
        $this->createNotification($employeeB, 'KGB', 'KGB B');

        $this->assertSame($pangkat->id, $this->repository->getNextPangkat()->id);
        $this->assertSame($kgb->id, $this->repository->getNextKGB()->id);
        $this->assertSame($retirement->id, $this->repository->getNextRetirement()->id);
        $this->assertSame($pangkat->id, $this->repository->getNextPangkatByUnitKerja('UNIT-A')->id);
        $this->assertSame($kgb->id, $this->repository->getNextKGBByUnitKerja('UNIT-A')->id);
        $this->assertSame($retirement->id, $this->repository->getNextRetirementByUnitKerja('UNIT-A')->id);
        $referenceDate = Carbon::parse($pangkat->tanggal_referensi)->toDateString();
        $this->assertSame($pangkat->id, $this->repository->findNotification($employeeA->nip, 'Pangkat', $referenceDate)->id);
        $this->assertNull($this->repository->findNotification($employeeB->nip, 'Pangkat', $referenceDate));
        $this->assertSame('2026-12-01', $this->repository->getNextPangkatByNip($employeeA->nip)->toDateString());
        $this->assertSame('2026-12-02', $this->repository->getNextKGBByNip($employeeA->nip)->toDateString());
        $this->assertSame('2027-01-01', $this->repository->getNextRetirementByNip($employeeA->nip)->toDateString());
        $this->assertNull($this->repository->getNextPangkatByNip($employeeB->nip));
        $this->assertNull($this->repository->getNextKGBByNip($employeeB->nip));
        $this->assertNull($this->repository->getNextRetirementByNip($employeeB->nip));
        $this->assertNull($this->repository->getNextPangkatByUnitKerja('UNIT-B'));
        $this->assertNull($this->repository->getNextRetirementByUnitKerja('UNIT-B'));
    }

    public function test_notification_required_lookup_throws_for_missing_or_unowned_records(): void
    {
        $employee = $this->createEmployee('198501012010011007', 'UNIT-A');
        $notification = $this->createNotification($employee, 'KGB', 'KGB A');

        $this->assertSame($notification->id, $this->repository->getById($notification->id, ['*'])->id);
        $this->expectException(ModelNotFoundException::class);
        $this->repository->getMyNotificationById($notification->id, '198501012010011008');
    }

    private function createEmployee(string $nip, string $unit, array $attributes = []): Employee
    {
        Department::firstOrCreate(
            ['kode' => $unit],
            ['nama' => $unit, 'status' => true]
        );

        return Employee::create(array_merge([
            'nip' => $nip,
            'nama' => "Pegawai {$nip}",
            'kode_unit_kerja' => $unit,
            'status' => 'Aktif',
        ], $attributes));
    }

    private function createNotification(
        Employee $employee,
        string $type,
        string $title,
        bool $read = false
    ): Notification {
        return Notification::create([
            'nip' => $employee->nip,
            'jenis' => $type,
            'judul' => $title,
            'pesan' => "Pesan {$title}",
            'tanggal_notifikasi' => today()->toDateString(),
            'tanggal_referensi' => today()->addMonths(1)->toDateString(),
            'sudah_dibaca' => $read,
        ]);
    }
}

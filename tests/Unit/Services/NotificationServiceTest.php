<?php

namespace Tests\Unit\Services;

use App\Models\Department;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Rank;
use App\Models\User;
use App\Jobs\SendEmailJob;
use App\Jobs\SendWhatsappJob;
use App\Repositories\NotificationRepository;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(NotificationRepository::class);
        $whatsAppService = Mockery::mock(\App\Services\WhatsAppService::class);
        $this->service = new NotificationService($this->repository, $whatsAppService);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_role_scoped_reads_and_summaries_delegate_to_the_repository(): void
    {
        $filters = ['jenis' => 'KGB'];
        $limit = 3;

        foreach ([
            ['super_admin', null, null],
            ['admin', 'UNIT-10', null],
            ['user', null, '199001012020011001'],
        ] as [$role, $unit, $nip]) {
            $user = new User([
                'role' => $role,
                'kode_unit_kerja' => $unit,
                'nip' => $nip,
            ]);
            $this->actingAs($user);
            $notification = new Notification(['jenis' => $role]);
            $page = new LengthAwarePaginator([$notification], 1, 15);
            $collection = new EloquentCollection([$notification]);

            [$listMethod, $listArguments] = match ($role) {
                'super_admin' => ['getAll', []],
                'admin' => ['getByUnitKerja', [$unit]],
                default => ['getByNip', [$nip]],
            };
            $this->repository->shouldReceive($listMethod)
                ->twice()
                ->with(...$listArguments)
                ->andReturn($role === 'user' ? $collection : $page);

            $statisticsMethods = match ($role) {
                'super_admin' => [
                    ['countAll', []],
                    ['countUnread', []],
                ],
                'admin' => [
                    ['countByUnitKerja', [$unit]],
                    ['countUnreadByUnitKerja', [$unit]],
                ],
                default => [
                    ['countByNip', [$nip]],
                    ['countUnreadByNip', [$nip]],
                ],
            };
            foreach ($statisticsMethods as $index => [$method, $arguments]) {
                $this->repository->shouldReceive($method)
                    ->once()
                    ->with(...$arguments)
                    ->andReturn($index + 2);
            }

            $markAllMethod = match ($role) {
                'super_admin' => ['markAllAsRead', []],
                'admin' => ['markAllAsReadByUnitKerja', [$unit]],
                default => ['markAllAsReadByNip', [$nip]],
            };
            $this->repository->shouldReceive($markAllMethod[0])
                ->once()
                ->with(...$markAllMethod[1])
                ->andReturn(1);

            $filteredMethod = match ($role) {
                'super_admin' => ['getFiltered', [$filters]],
                'admin' => ['getFilteredByUnitKerja', [$filters, $unit]],
                default => ['getFilteredByNip', [$filters, $nip]],
            };
            $this->repository->shouldReceive($filteredMethod[0])
                ->once()
                ->with(...$filteredMethod[1])
                ->andReturn($page);

            $summaryMethods = match ($role) {
                'super_admin' => [
                    ['getNextPangkat', []],
                    ['getNextKGB', []],
                    ['getNextRetirement', []],
                ],
                'admin' => [
                    ['getNextPangkatByUnitKerja', [$unit]],
                    ['getNextKGBByUnitKerja', [$unit]],
                    ['getNextRetirementByUnitKerja', [$unit]],
                ],
                default => [
                    ['getNextPangkatByNip', [$nip]],
                    ['getNextKGBByNip', [$nip]],
                    ['getNextRetirementByNip', [$nip]],
                ],
            };
            $summary = [];
            foreach ($summaryMethods as $index => [$method, $arguments]) {
                $value = $role === 'user'
                    ? ['pangkat', 'kgb', 'pensiun'][$index]
                    : new Notification([
                        'jenis' => ['Pangkat', 'KGB', 'Pensiun'][$index],
                    ]);
                $this->repository->shouldReceive($method)
                    ->once()
                    ->with(...$arguments)
                    ->andReturn($value);
                $summaryKey = ['pangkat', 'kgb', 'pensiun'][$index];
                $summary[$summaryKey] = $value;
            }

            $latestMethod = match ($role) {
                'super_admin' => ['getLatest', [null, $limit]],
                'admin' => ['getLatestByUnitKerja', [$unit, $limit]],
                default => ['getLatestByNip', [$nip, $limit]],
            };
            $this->repository->shouldReceive($latestMethod[0])
                ->once()
                ->with(...$latestMethod[1])
                ->andReturn($collection);

            $this->assertSame(
                $role === 'user' ? $collection : $page,
                $this->service->getNotifications()
            );
            $this->assertSame(
                $role === 'user' ? $collection : $page,
                $this->service->getHistory()
            );
            $this->assertSame([
                'total' => 2,
                'belumDibaca' => 3,
            ], $this->service->getStatistics());
            $this->assertSame(1, $this->service->markAllAsRead());
            $this->assertSame($page, $this->service->getFiltered($filters, $user));
            $this->assertSame([
                'pangkat' => $summary['pangkat'],
                'kgb' => $summary['kgb'],
                'pensiun' => $summary['pensiun'],
            ], $this->service->getSummary());
            $this->assertSame(
                $collection,
                $this->service->getLatestNotifications($limit)
            );
        }
    }

    public function test_log_creates_unread_notification_with_reference_and_delivery_defaults(): void
    {
        $expectedDate = now('Asia/Jakarta')->toDateString();
        $notification = new Notification();
        $this->repository->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data) use ($expectedDate): bool {
                $this->assertSame('199001012020011001', $data['nip']);
                $this->assertSame('Pangkat', $data['jenis']);
                $this->assertSame('Jadwal pangkat', $data['judul']);
                $this->assertSame('Pesan notifikasi', $data['pesan']);
                $this->assertFalse($data['sudah_dibaca']);
                $this->assertSame($expectedDate, $data['tanggal_referensi']);
                $this->assertSame($expectedDate, $data['tanggal_notifikasi']);
                $this->assertFalse($data['email_terkirim']);
                $this->assertFalse($data['whatsapp_terkirim']);
                $this->assertFalse($data['email_super_admin_terkirim']);
                $this->assertFalse($data['whatsapp_super_admin_terkirim']);

                return true;
            })
            ->andReturn($notification);

        $this->assertSame(
            $notification,
            $this->service->log(
                '199001012020011001',
                'Jadwal pangkat',
                'Pesan notifikasi',
                'Pangkat'
            )
        );
    }

    public function test_schedule_summary_methods_return_dates_or_null_when_missing(): void
    {
        $employee = Employee::create([
            'nip' => '199001012020011001',
            'nama' => 'Pegawai Jadwal',
            'tmt_pangkat_baru' => '2027-01-01',
            'tmt_kgb_baru' => '2027-02-01',
            'tmt_pensiun' => '2050-03-01',
        ]);

        foreach ([
            'getNextPangkat' => 'tmt_pangkat_baru',
            'getNextKGB' => 'tmt_kgb_baru',
            'getRetirement' => 'tmt_pensiun',
        ] as $method => $column) {
            $result = $this->service->{$method}($employee->nip);

            $this->assertSame(
                $employee->{$column}->toDateString(),
                $result['tanggal']->toDateString()
            );
            $this->assertIsString($result['selisih']);
            $this->assertNotSame('', $result['selisih']);

            $employee->{$column} = null;
            $employee->save();
            $this->assertNull($this->service->{$method}($employee->nip));
            $employee->refresh();
        }
    }

    /**
     * Memeriksa generatePangkat diabaikan untuk pegawai PPPK.
     */
    public function test_generate_pangkat_diabaikan_untuk_pppk(): void
    {
        $edu = new EducationLevel([
            'kode' => 'S1',
            'pangkat_pns_maksimum' => 12,
        ]);
        $rank = new Rank(['urutan_pangkat' => 1]);
        $pegawai = new Employee([
            'status' => 'Aktif',
            'status_pegawai' => 'PPPK',
            'nama' => 'Pegawai PPPK',
            'tmt_pangkat_baru' => Carbon::today()->addMonth()->format('Y-m-d'),
        ]);
        $pegawai->setRelation('educationLevel', $edu);
        $pegawai->setRelation('rank', $rank);

        $this->repository->shouldNotReceive('create');
        $this->service->generatePangkat($pegawai);
    }

    /**
     * Memeriksa generatePangkat diabaikan jika pangkat pegawai telah mencapai batas pendidikan.
     */
    public function test_generate_pangkat_diabaikan_jika_sudah_mencapai_pangkat_maksimum_pendidikan(): void
    {
        $edu = new EducationLevel([
            'kode' => 'SLTA',
            'pangkat_pns_maksimum' => 4, // II/d
        ]);

        $rank = new Rank([
            'urutan_pangkat' => 4,
        ]);

        $pegawai = new Employee([
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
            'tmt_pangkat_baru' => Carbon::today()->addMonth()->format('Y-m-d'),
        ]);
        $pegawai->setRelation('educationLevel', $edu);
        $pegawai->setRelation('rank', $rank);

        $this->repository->shouldNotReceive('create');
        $this->service->generatePangkat($pegawai);
        $this->assertTrue(true);
    }

    /**
     * Memeriksa generatePangkat diabaikan jika tanggal hari ini lebih dari 2 bulan sebelum TMT Pangkat.
     */
    public function test_generate_pangkat_diabaikan_jika_lebih_dari_2_bulan_sebelum_tmt(): void
    {
        $edu = new EducationLevel([
            'kode' => 'S1',
            'pangkat_pns_maksimum' => 12,
        ]);
        $rank = new Rank(['urutan_pangkat' => 9]);

        $pegawai = new Employee([
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
            'tmt_pangkat_baru' => Carbon::today()->addMonths(3)->format('Y-m-d'),
        ]);
        $pegawai->setRelation('educationLevel', $edu);
        $pegawai->setRelation('rank', $rank);

        $this->repository->shouldNotReceive('create');
        $this->service->generatePangkat($pegawai);
        $this->assertTrue(true);
    }

    /**
     * Memeriksa generatePangkat berhasil memicu notifikasi jika memenuhi syarat dan dalam 2 bulan.
     */
    public function test_generate_pangkat_berhasil_membuat_notifikasi_jika_dalam_2_bulan(): void
    {
        $edu = new EducationLevel([
            'kode' => 'S1',
            'pangkat_pns_maksimum' => 12,
        ]);
        $rank = new Rank(['urutan_pangkat' => 9]);

        $tmt = Carbon::today()->addDays(30);

        $pegawai = new Employee([
            'status' => 'Aktif',
            'nip' => '199001012015011001',
            'nama' => 'Budi Santoso',
            'status_pegawai' => 'PNS',
            'tmt_pangkat_baru' => $tmt->format('Y-m-d'),
        ]);
        $pegawai->setRelation('educationLevel', $edu);
        $pegawai->setRelation('rank', $rank);

        $dummyNotification = new Notification([
            'nip' => $pegawai->nip,
            'jenis' => 'Pangkat',
            'email_terkirim' => true,
            'whatsapp_terkirim' => true,
            'email_super_admin_terkirim' => true,
            'whatsapp_super_admin_terkirim' => true,
        ]);

        $this->repository->shouldReceive('findNotification')->once()->andReturnNull();
        $this->repository->shouldReceive('create')->once()->andReturn($dummyNotification);

        $this->service->generatePangkat($pegawai);
        $this->assertTrue(true);
    }

    /**
     * Memeriksa generateKGB diabaikan jika lebih dari 2 bulan sebelum TMT KGB.
     */
    public function test_generate_kgb_diabaikan_jika_lebih_dari_2_bulan(): void
    {
        $pegawai = new Employee([
            'status' => 'Aktif',
            'tmt_kgb_baru' => Carbon::today()->addMonths(4)->format('Y-m-d'),
        ]);

        $this->repository->shouldNotReceive('create');
        $this->service->generateKGB($pegawai);
        $this->assertTrue(true);
    }

    public function test_generate_kgb_does_not_notify_before_the_two_month_window(): void
    {
        $pegawai = new Employee([
            'status' => 'Aktif',
            'tmt_kgb_baru' => Carbon::today()->addMonths(3)->toDateString(),
        ]);

        $this->repository->shouldNotReceive('create');

        $this->service->generateKGB($pegawai);
    }

    /**
     * Memeriksa generatePensiun diabaikan jika lebih dari 6 bulan sebelum TMT Pensiun.
     */
    public function test_generate_pensiun_diabaikan_jika_lebih_dari_6_bulan(): void
    {
        $pegawai = new Employee([
            'status' => 'Aktif',
            'tmt_pensiun' => Carbon::today()->addMonths(8)->format('Y-m-d'),
        ]);

        $this->repository->shouldNotReceive('create');
        $this->service->generatePensiun($pegawai);
        $this->assertTrue(true);
    }

    public function test_generate_pensiun_does_not_notify_before_the_six_month_window(): void
    {
        $pegawai = new Employee([
            'status' => 'Aktif',
            'tmt_pensiun' => Carbon::today()->addMonths(7)->toDateString(),
        ]);

        $this->repository->shouldNotReceive('create');

        $this->service->generatePensiun($pegawai);
    }

    /**
     * Memeriksa generatePensiun aktif saat memasuki 6 bulan sebelum TMT Pensiun.
     */
    public function test_generate_pensiun_aktif_saat_memasuki_6_bulan(): void
    {
        $tmt = Carbon::today()->addMonths(5);

        $pegawai = new Employee([
            'status' => 'Aktif',
            'nip' => '196801011990011001',
            'nama' => 'Siti Aminah',
            'tmt_pensiun' => $tmt->format('Y-m-d'),
        ]);

        $dummyNotification = new Notification([
            'nip' => $pegawai->nip,
            'jenis' => 'Pensiun',
            'email_terkirim' => true,
            'whatsapp_terkirim' => true,
            'email_super_admin_terkirim' => true,
            'whatsapp_super_admin_terkirim' => true,
        ]);

        $this->repository->shouldReceive('findNotification')->once()->andReturnNull();
        $this->repository->shouldReceive('create')->once()->andReturn($dummyNotification);

        $this->service->generatePensiun($pegawai);
        $this->assertTrue(true);
    }

    public function test_generate_notification_stops_for_nonactive_employees(): void
    {
        $pegawai = new Employee([
            'status' => 'Pensiun',
            'status_pegawai' => 'PNS',
            'tmt_pangkat_baru' => Carbon::today()->addMonth()->toDateString(),
            'tmt_kgb_baru' => Carbon::today()->addMonth()->toDateString(),
            'tmt_pensiun' => Carbon::today()->addMonth()->toDateString(),
        ]);

        $this->repository->shouldNotReceive('findNotification');
        $this->repository->shouldNotReceive('create');

        $this->service->generateNotification($pegawai);
    }

    public function test_generate_pangkat_stops_for_missing_rank_data_maximum_or_tmt(): void
    {
        $rank = new Rank(['urutan_pangkat' => 1]);
        $employees = [];

        $missingRank = new Employee([
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
            'tmt_pangkat_baru' => Carbon::today()->addMonth()->toDateString(),
        ]);
        $missingRank->setRelation('educationLevel', new EducationLevel([
            'pangkat_pns_maksimum' => 12,
        ]));
        $missingRank->setRelation('rank', null);
        $employees[] = $missingRank;

        $missingMaximum = new Employee([
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
            'tmt_pangkat_baru' => Carbon::today()->addMonth()->toDateString(),
        ]);
        $missingMaximum->setRelation('educationLevel', new EducationLevel([
            'pangkat_pns_maksimum' => null,
        ]));
        $missingMaximum->setRelation('rank', $rank);
        $employees[] = $missingMaximum;

        $missingTmt = new Employee([
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
        ]);
        $missingTmt->setRelation('educationLevel', new EducationLevel([
            'pangkat_pns_maksimum' => 12,
        ]));
        $missingTmt->setRelation('rank', $rank);
        $employees[] = $missingTmt;

        $this->repository->shouldNotReceive('findNotification');
        $this->repository->shouldNotReceive('create');

        foreach ($employees as $pegawai) {
            $this->service->generatePangkat($pegawai);
        }
    }

    public function test_processed_kgb_notification_stops_when_employee_or_dates_are_invalid(): void
    {
        Queue::fake();
        $employees = [
            $this->createEmployeeForNotificationTest('199001012020012001', [
                'status' => 'Pensiun',
                'tmt_kgb_lama' => '2024-01-01',
                'tmt_kgb_baru' => '2026-01-01',
            ]),
            $this->createEmployeeForNotificationTest('199001012020012002', [
                'tmt_kgb_lama' => '2024-01-01',
            ]),
            $this->createEmployeeForNotificationTest('199001012020012003', [
                'tmt_kgb_baru' => '2026-01-01',
            ]),
        ];

        $this->repository->shouldNotReceive('findNotification');
        $this->repository->shouldNotReceive('create');

        foreach ($employees as $pegawai) {
            $this->service->notifyKgbProcessed($pegawai, 3_100_000);
        }

        Queue::assertNothingPushed();
    }

    public function test_processed_kgb_uses_the_current_employee_status(): void
    {
        Queue::fake();
        $pegawai = $this->createEmployeeForNotificationTest('199001012020012009', [
            'tmt_kgb_lama' => '2024-01-01',
            'tmt_kgb_baru' => '2026-01-01',
        ]);
        Employee::query()
            ->whereKey($pegawai->getKey())
            ->update(['status' => 'Pensiun']);

        $this->repository->shouldNotReceive('findNotification');
        $this->repository->shouldNotReceive('create');

        $this->service->notifyKgbProcessed($pegawai, 3_100_000);

        Queue::assertNothingPushed();
    }

    public function test_processed_kgb_dispatches_only_uncompleted_super_admin_channels(): void
    {
        Queue::fake();
        User::factory()->create([
            'role' => 'super_admin',
            'email' => 'admin@example.test',
            'no_hp' => '081111111111',
        ]);

        $completedEmployee = $this->createEmployeeForNotificationTest('199001012020012010', [
            'tmt_kgb_lama' => '2024-01-01',
            'tmt_kgb_baru' => '2026-01-01',
        ]);
        $incompleteEmployee = $this->createEmployeeForNotificationTest('199001012020012011', [
            'tmt_kgb_lama' => '2024-02-01',
            'tmt_kgb_baru' => '2026-02-01',
        ]);
        $pendingBothEmployee = $this->createEmployeeForNotificationTest('199001012020012012', [
            'tmt_kgb_lama' => '2024-03-01',
            'tmt_kgb_baru' => '2026-03-01',
        ]);

        $completedNotification = $this->createNotificationForResend(
            $completedEmployee,
            'KGB Diproses',
            '2026-01-01',
            [
                'email_terkirim' => true,
                'whatsapp_terkirim' => true,
                'email_super_admin_terkirim' => true,
                'whatsapp_super_admin_terkirim' => true,
            ]
        );
        $incompleteNotification = $this->createNotificationForResend(
            $incompleteEmployee,
            'KGB Diproses',
            '2026-02-01',
            [
                'email_terkirim' => true,
                'whatsapp_terkirim' => true,
                'email_super_admin_terkirim' => true,
                'whatsapp_super_admin_terkirim' => false,
            ]
        );
        $pendingBothNotification = $this->createNotificationForResend(
            $pendingBothEmployee,
            'KGB Diproses',
            '2026-03-01',
            [
                'email_terkirim' => true,
                'whatsapp_terkirim' => true,
                'email_super_admin_terkirim' => false,
                'whatsapp_super_admin_terkirim' => false,
            ]
        );

        $this->repository->shouldReceive('findNotification')
            ->times(3)
            ->andReturnNull();
        $this->repository->shouldReceive('create')
            ->times(3)
            ->andReturn($completedNotification, $incompleteNotification, $pendingBothNotification);

        $this->service->notifyKgbProcessed($completedEmployee, 3_100_000);
        $this->service->notifyKgbProcessed($incompleteEmployee, 3_100_000);
        $this->service->notifyKgbProcessed($pendingBothEmployee, 3_100_000);

        Queue::assertPushed(SendEmailJob::class, 1);
        Queue::assertPushed(
            SendEmailJob::class,
            fn (SendEmailJob $job): bool => $job->notification->nip === $pendingBothEmployee->nip
                && $job->email === 'admin@example.test'
                && $job->isSuperAdmin
        );
        Queue::assertPushed(SendWhatsappJob::class, 2);
        Queue::assertPushed(
            SendWhatsappJob::class,
            fn (SendWhatsappJob $job): bool => $job->notification->nip === $incompleteEmployee->nip
                && $job->no_hp === '081111111111'
                && $job->isSuperAdmin
        );
        Queue::assertPushed(
            SendWhatsappJob::class,
            fn (SendWhatsappJob $job): bool => $job->notification->nip === $pendingBothEmployee->nip
                && $job->no_hp === '081111111111'
                && $job->isSuperAdmin
        );
    }

    public function test_resend_pending_notifications_filters_by_age_and_pending_channels(): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::parse('2026-11-15 12:00:00', 'Asia/Jakarta'));
        $pegawai = $this->createEmployeeForNotificationTest('199001012020012004', [
            'email' => 'pegawai@example.test',
            'no_hp' => '081234567890',
        ]);
        User::factory()->create([
            'role' => 'super_admin',
            'email' => 'super-admin@example.test',
            'no_hp' => '081234567899',
        ]);
        $this->createNotificationForResend($pegawai, 'KGB', '2021-11-15', [
            'email_terkirim' => true,
            'whatsapp_terkirim' => true,
            'email_super_admin_terkirim' => true,
            'whatsapp_super_admin_terkirim' => true,
        ]);
        $pendingEmail = $this->createNotificationForResend($pegawai, 'Pangkat', '2021-11-14', [
            'whatsapp_terkirim' => true,
        ]);
        $pendingWhatsapp = $this->createNotificationForResend($pegawai, 'Pensiun', '2019-01-01', [
            'email_terkirim' => true,
        ]);
        $this->createNotificationForResend($pegawai, 'KGB Diproses', '2018-01-01', [
            'email_terkirim' => true,
            'whatsapp_terkirim' => true,
        ]);
        $this->createNotificationForResend($pegawai, 'KGB Diproses', null);

        $this->service->resendPendingNotifications($pegawai);

        Queue::assertPushed(SendEmailJob::class, 1);
        Queue::assertPushed(
            SendEmailJob::class,
            fn (SendEmailJob $job): bool => $job->notification->is($pendingEmail)
                && $job->email === 'pegawai@example.test'
                && !$job->isSuperAdmin
        );
        Queue::assertPushed(SendWhatsappJob::class, 1);
        Queue::assertPushed(
            SendWhatsappJob::class,
            fn (SendWhatsappJob $job): bool => $job->notification->is($pendingWhatsapp)
                && $job->no_hp === '081234567890'
                && !$job->isSuperAdmin
        );
    }

    public function test_resend_pending_notifications_excludes_pangkat_and_kgb_for_retired_employees(): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::parse('2026-11-15 12:00:00', 'Asia/Jakarta'));
        $pegawai = $this->createEmployeeForNotificationTest('199001012020012005', [
            'status' => 'Pensiun',
            'email' => 'pensiun@example.test',
            'no_hp' => '081234567890',
        ]);
        $this->createNotificationForResend($pegawai, 'Pangkat', '2026-01-01');
        $this->createNotificationForResend($pegawai, 'KGB', '2026-02-01');
        $pensiun = $this->createNotificationForResend($pegawai, 'Pensiun', '2026-03-01');
        $kgbDiproses = $this->createNotificationForResend($pegawai, 'KGB Diproses', '2026-04-01');

        $this->service->resendPendingNotifications($pegawai);

        Queue::assertPushed(SendEmailJob::class, 2);
        Queue::assertPushed(
            SendEmailJob::class,
            fn (SendEmailJob $job): bool => $job->notification->is($pensiun)
                && $job->email === 'pensiun@example.test'
        );
        Queue::assertPushed(
            SendEmailJob::class,
            fn (SendEmailJob $job): bool => $job->notification->is($kgbDiproses)
                && $job->email === 'pensiun@example.test'
        );
        Queue::assertPushed(SendWhatsappJob::class, 2);
        Queue::assertPushed(
            SendWhatsappJob::class,
            fn (SendWhatsappJob $job): bool => $job->notification->is($pensiun)
                && $job->no_hp === '081234567890'
        );
        Queue::assertPushed(
            SendWhatsappJob::class,
            fn (SendWhatsappJob $job): bool => $job->notification->is($kgbDiproses)
                && $job->no_hp === '081234567890'
        );
    }

    public function test_resend_pending_notifications_does_nothing_when_no_notification_matches(): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::parse('2026-11-15 12:00:00', 'Asia/Jakarta'));
        $pegawai = $this->createEmployeeForNotificationTest('199001012020012006');
        $this->createNotificationForResend($pegawai, 'KGB', '2020-01-01', [
            'email_terkirim' => true,
            'whatsapp_terkirim' => true,
        ]);
        $this->createNotificationForResend($pegawai, 'Pensiun', null);

        $this->service->resendPendingNotifications($pegawai);

        Queue::assertNothingPushed();
    }

    public function test_get_notifications_rejects_unknown_role_and_missing_identity(): void
    {
        foreach ([
            [new User(['role' => 'operator']), 403],
            [new User(['role' => 'admin']), 403],
            [new User(['role' => 'user']), 403],
        ] as [$user, $status]) {
            $this->actingAs($user);

            try {
                $this->service->getNotifications();
                $this->fail('Seharusnya akses ditolak.');
            } catch (HttpException $exception) {
                $this->assertSame($status, $exception->getStatusCode());
            }
        }

        $this->app['auth']->forgetGuards();

        try {
            $this->service->getNotifications();
            $this->fail('Seharusnya pengguna yang belum terautentikasi ditolak.');
        } catch (HttpException $exception) {
            $this->assertSame(401, $exception->getStatusCode());
        }
    }

    private function createEmployeeForNotificationTest(string $nip, array $attributes = []): Employee
    {
        return Employee::create(array_merge([
            'nip' => $nip,
            'nama' => "Pegawai {$nip}",
            'status' => 'Aktif',
        ], $attributes));
    }

    private function createNotificationForResend(
        Employee $pegawai,
        string $jenis,
        ?string $tanggalReferensi,
        array $attributes = []
    ): Notification {
        return Notification::create(array_merge([
            'nip' => $pegawai->nip,
            'jenis' => $jenis,
            'judul' => "Notifikasi {$jenis}",
            'pesan' => "Pesan {$jenis}",
            'tanggal_notifikasi' => now('Asia/Jakarta')->toDateString(),
            'tanggal_referensi' => $tanggalReferensi,
        ], $attributes));
    }

    /**
     * Memeriksa markAsRead menolak Admin jika notifikasi bukan kewenangan unit kerjanya.
     */
    public function test_mark_as_read_menolak_admin_jika_bukan_unit_kerjanya(): void
    {
        $admin = new User([
            'role' => 'admin',
            'kode_unit_kerja' => 'KEC-01',
        ]);
        $this->actingAs($admin);

        $this->repository->shouldReceive('getByUnitKerjaNotification')
            ->once()
            ->with(15, 'KEC-01')
            ->andReturnNull();

        try {
            $this->service->markAsRead(15);
            $this->fail('Seharusnya ditolak 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame('Notifikasi tidak ditemukan atau bukan kewenangan unit kerja Anda.', $e->getMessage());
        }
    }

    /**
     * Memeriksa markAsRead menolak User jika notifikasi bukan miliknya.
     */
    public function test_mark_as_read_menolak_user_jika_bukan_notifikasi_miliknya(): void
    {
        $user = new User([
            'role' => 'user',
            'nip' => '199001012015011001',
        ]);
        $this->actingAs($user);

        $this->repository->shouldReceive('getByNipNotification')
            ->once()
            ->with(20, '199001012015011001')
            ->andReturnNull();

        try {
            $this->service->markAsRead(20);
            $this->fail('Seharusnya ditolak 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame('Notifikasi bukan milik Anda.', $e->getMessage());
        }
    }
}

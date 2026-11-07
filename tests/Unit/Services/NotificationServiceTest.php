<?php

namespace Tests\Unit\Services;

use App\Models\Department;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Rank;
use App\Models\User;
use App\Repositories\NotificationRepository;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    /**
     * Memeriksa generatePangkat diabaikan untuk pegawai PPPK.
     */
    public function test_generate_pangkat_diabaikan_untuk_pppk(): void
    {
        $pegawai = new Employee([
            'status' => 'Aktif',
            'status_pegawai' => 'PPPK',
            'tmt_pangkat_baru' => Carbon::today()->addMonth()->format('Y-m-d'),
        ]);

        $this->repository->shouldNotReceive('create');
        $this->service->generatePangkat($pegawai);
        $this->assertTrue(true);
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

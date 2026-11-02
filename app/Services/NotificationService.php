<?php
namespace App\Services;
use App\Jobs\SendEmailJob;
use App\Jobs\SendWhatsappJob;
use App\Models\Employee;
use App\Models\Notification;
use App\Repositories\NotificationRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
class NotificationService
{
    private ?User $superAdmin = null;
    private const RESEND_NOTIFICATION_YEARS = 5;
    private const EMAIL_DELAY = 60;
    private const WA_DELAY = 900;
    private const WA_CACHE_KEY = 'notification_wa_next_time';
    private const EMAIL_CACHE_KEY = 'notification_email_next_time';
    public function __construct(
        protected NotificationRepository $notification_repository,
        protected WhatsAppService $whats_app_service,
    ) {}
    /*
    |--------------------------------------------------------------------------
    | Function Lama (Tetap Dipakai)
    |--------------------------------------------------------------------------
    */
    public function getNotifications()
    {
        $user = $this->getAuthenticatedUser();

        if ($user->role === 'super_admin') {
            return $this->notification_repository->getAll();
        }

        if ($user->role === 'admin') {
            $unit = $this->getAdminUnit($user);

            return $this->notification_repository->getByUnitKerja($unit);
        }

        if ($user->role === 'user') {
            $nip = $this->getUserNip($user);

            return $this->notification_repository->getByNip($nip);
        }

        abort(403);
    }
    public function getStatistics(): array
    {
        $user = $this->getAuthenticatedUser();

        if ($user->role === 'super_admin') {
            return [
                'total' => $this->notification_repository->countAll(),
                'belumDibaca' => $this->notification_repository->countUnread(),
            ];
        }

        if ($user->role === 'admin') {
            $unit = $this->getAdminUnit($user);

            return [
                'total' => $this->notification_repository->countByUnitKerja($unit),
                'belumDibaca' => $this->notification_repository->countUnreadByUnitKerja($unit),
            ];
        }

        if ($user->role === 'user') {
            $nip = $this->getUserNip($user);

            return [
                'total' => $this->notification_repository->countByNip($nip),
                'belumDibaca' => $this->notification_repository->countUnreadByNip($nip),
            ];
        }

        abort(403);
    }
    public function log(string $nip,string $judul,string $pesan,string $jenis = 'system') {
        return $this->notification_repository->create([
            'nip' => $nip,
            'jenis' => $jenis,
            'judul' => $judul,
            'pesan' => $pesan,
            'sudah_dibaca' => false,
            'tanggal_referensi' => now()->toDateString(),
            'tanggal_notifikasi' => now()->toDateString(),
            'email_terkirim' => false,
            'whatsapp_terkirim' => false,
            'email_super_admin_terkirim'    => false,
            'whatsapp_super_admin_terkirim' => false,
        ]);
    }
    public function markAsRead(int $id)
    {
        $user = $this->getAuthenticatedUser();

        if ($user->role === 'super_admin') {
            return $this->notification_repository->markAsRead($id);
        }

        if ($user->role === 'admin') {
            $unit = $this->getAdminUnit($user);

            $notification = $this->notification_repository
                ->getByUnitKerjaNotification($id, $unit);

            abort_if(!$notification, 403, 'Notifikasi tidak ditemukan atau bukan kewenangan unit kerja Anda.');

            $notification->update([
                'sudah_dibaca' => true,
            ]);

            return $notification;
        }

        if ($user->role === 'user') {
            $nip = $this->getUserNip($user);

            $notification = $this->notification_repository
                ->getByNipNotification($id, $nip);

            abort_if(!$notification, 403, 'Notifikasi bukan milik Anda.');

            $notification->update([
                'sudah_dibaca' => true,
            ]);

            return $notification;
        }

        abort(403);
    }
    public function markAllAsRead()
    {
        $user = $this->getAuthenticatedUser();

        if ($user->role === 'super_admin') {
            return $this->notification_repository->markAllAsRead();
        }

        if ($user->role === 'admin') {
            $unit = $this->getAdminUnit($user);

            return $this->notification_repository
                ->markAllAsReadByUnitKerja($unit);
        }

        if ($user->role === 'user') {
            $nip = $this->getUserNip($user);

            return $this->notification_repository
                ->markAllAsReadByNip($nip);
        }

        abort(403);
    }
    public function getFiltered(array $filters, $user)
    {
        if (!$user instanceof User) {
            abort(403);
        }

        if ($user->role === 'super_admin') {
            return $this->notification_repository->getFiltered($filters);
        }

        if ($user->role === 'admin') {
            $unit = $this->getAdminUnit($user);

            return $this->notification_repository
                ->getFilteredByUnitKerja($filters, $unit);
        }

        if ($user->role === 'user') {
            $nip = $this->getUserNip($user);

            return $this->notification_repository
                ->getFilteredByNip($filters, $nip);
        }

        abort(403);
    }
    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */
    public function getNextPangkat(string $nip)
    {
        $pegawai = Employee::findOrFail($nip);
        if (!$pegawai->tmt_pangkat_baru) {
            return null;
        }
        return [
            'tanggal' => $pegawai->tmt_pangkat_baru,
            'selisih' => Carbon::parse($pegawai->tmt_pangkat_baru)->diffForHumans([
                'parts' => 3,
                'join' => true,
            ]),
        ];
    }
    public function getNextKGB(string $nip)
    {
        $pegawai = Employee::findOrFail($nip);
        if (!$pegawai->tmt_kgb_baru) {
            return null;
        }
        return [
            'tanggal' => $pegawai->tmt_kgb_baru,
            'selisih' => Carbon::parse($pegawai->tmt_kgb_baru)->diffForHumans([
                'parts' => 3,
                'join' => true,
            ]),
        ];
    }
    public function getRetirement(string $nip)
    {
        $pegawai = Employee::findOrFail($nip);
        if (!$pegawai->tmt_pensiun) {
            return null;
        }
        return [
            'tanggal' => $pegawai->tmt_pensiun,
            'selisih' => Carbon::parse($pegawai->tmt_pensiun)->diffForHumans([
                'parts' => 3,
                'join' => true,
            ]),
        ];
    }
    public function getSummary(): array
    {
        $user = $this->getAuthenticatedUser();

        if ($user->role === 'super_admin') {
            return [
                'pangkat' => $this->notification_repository->getNextPangkat(),
                'kgb' => $this->notification_repository->getNextKGB(),
                'pensiun' => $this->notification_repository->getNextRetirement(),
            ];
        }

        if ($user->role === 'admin') {
            $unit = $this->getAdminUnit($user);

            return [
                'pangkat' => $this->notification_repository->getNextPangkatByUnitKerja($unit),
                'kgb' => $this->notification_repository->getNextKGBByUnitKerja($unit),
                'pensiun' => $this->notification_repository->getNextRetirementByUnitKerja($unit),
            ];
        }

        if ($user->role === 'user') {
            $nip = $this->getUserNip($user);

            return [
                'pangkat' => $this->notification_repository->getNextPangkatByNip($nip),
                'kgb' => $this->notification_repository->getNextKGBByNip($nip),
                'pensiun' => $this->notification_repository->getNextRetirementByNip($nip),
            ];
        }

        abort(403);
    }
    public function getHistory()
    {
        return $this->getNotifications();
    }

    public function getLatestNotifications(int $limit = 5)
    {
        $user = $this->getAuthenticatedUser();

        if ($user->role === 'super_admin') {
            return $this->notification_repository->getLatest(null, $limit);
        }

        if ($user->role === 'admin') {
            $unit = $this->getAdminUnit($user);

            return $this->notification_repository
                ->getLatestByUnitKerja($unit, $limit);
        }

        if ($user->role === 'user') {
            $nip = $this->getUserNip($user);

            return $this->notification_repository
                ->getLatestByNip($nip, $limit);
        }

        abort(403);
    }
    
    /*
    |--------------------------------------------------------------------------
    | Generate Notification
    |--------------------------------------------------------------------------
    */
    public function generateNotification(Employee $pegawai): void
    {
        $this->generatePangkat($pegawai);
        $this->generateKGB($pegawai);
        $this->generatePensiun($pegawai);
    }
    public function generateNotifications(): void
    {
        Employee::query()->chunkById(
                100,
                function ($employees) {
                    foreach ($employees as $pegawai) {
                        $this->generateNotification($pegawai);
                    }
                },'nip','nip'
            );
    }
    public function generatePangkat(Employee $pegawai): void
    {
        // PPPK tidak menerima notifikasi kenaikan pangkat
        if ($pegawai->status_pegawai !== 'PNS') {
            return;
        }
        // Jika data pendidikan tidak tersedia, hentikan proses
        if (!$pegawai->educationLevel) {
            return;
        }
        // Ambil batas maksimum pangkat berdasarkan pendidikan
        $maksimumPangkat = $pegawai->educationLevel->pangkat_pns_maksimum;
        // Jika pangkat saat ini sudah mencapai batas maksimum pendidikan,
        // maka notifikasi kenaikan pangkat tidak dibuat
        if ( $pegawai->rank && $pegawai->rank->urutan_pangkat >= $maksimumPangkat) {
            return;
        }
        // Jika TMT pangkat baru tidak tersedia, hentikan proses
        if (empty($pegawai->tmt_pangkat_baru)) {
            return;
        }
        $referenceDate = Carbon::parse($pegawai->tmt_pangkat_baru);
        // Notifikasi aktif 2 bulan sebelum TMT pangkat baru
        if (Carbon::today()->lt($referenceDate->copy()->subMonths(2))) {
            return;
        }
        $pesan = "Yth. Bapak/Ibu {$pegawai->nama},\n\n"
            . "Berdasarkan data kepegawaian, Anda akan memasuki periode Kenaikan Pangkat.\n\n"
            . "Tanggal Efektif: "
            . $referenceDate->translatedFormat('d F Y')
            . "\n\n"
            . "Mohon segera mempersiapkan dokumen administrasi yang diperlukan untuk proses Kenaikan Pangkat Anda.\n\n"
            . "Terima kasih.";
        $this->createNotification(
            $pegawai,
            'Pangkat',
            'Pemberitahuan Kenaikan Pangkat',
            $pesan,
            $referenceDate
        );
    }
    public function generateKGB(Employee $pegawai): void
    {
        if (empty($pegawai->tmt_kgb_baru)) {
            return;
        }
        $referenceDate = Carbon::parse($pegawai->tmt_kgb_baru);
        // Notifikasi muncul 2 bulan sebelum KGB
        if (Carbon::today()->lt($referenceDate->copy()->subMonths(2))) {
            return;
        }
        $pesan = "Yth. Bapak/Ibu {$pegawai->nama},\n\n"
            . "Berdasarkan data kepegawaian, Anda akan memasuki periode Kenaikan Gaji Berkala (KGB).\n\n"
            . "Tanggal Efektif: "
            . $referenceDate->translatedFormat('d F Y')
            . "\n\n"
            . "Mohon segera mempersiapkan dokumen administrasi yang diperlukan untuk proses Kenaikan Gaji Berkala (KGB) Anda.\n\n"
            . "Terima kasih.";
        $this->createNotification(
            $pegawai,
            'KGB',
            'Pemberitahuan Kenaikan Gaji Berkala',
            $pesan,
            $referenceDate
        );
    }
    public function generatePensiun(Employee $pegawai): void
    {
        if (empty($pegawai->tmt_pensiun)) {
            return;
        }
        $referenceDate = Carbon::parse($pegawai->tmt_pensiun);
        if (Carbon::today()->lt($referenceDate->copy()->subMonths(6))) {
            return;
        }
        $pesan = "Yth. Bapak/Ibu {$pegawai->nama},\n\n"
            . "Berdasarkan data kepegawaian, Anda akan memasuki masa pensiun.\n\n"
            . "Tanggal Efektif: "
            . $referenceDate->translatedFormat('d F Y')
            . "\n\n"
            . "Mohon segera mempersiapkan dokumen administrasi dan menyelesaikan proses pemberkasan pensiun Anda.\n\n"
            . "Terima kasih.";
        $this->createNotification(
            $pegawai,
            'Pensiun',
            'Pemberitahuan Masa Pensiun',
            $pesan,
            $referenceDate
        );
    }
    public function notifyKgbProcessed(Employee $pegawai,int $gajiBaru): void {
        $pegawai->refresh();
        /*
        |--------------------------------------------------------------------------
        | Reference date = TMT KGB berikutnya
        |--------------------------------------------------------------------------
        */
        $referenceDate = Carbon::parse($pegawai->tmt_kgb_baru);
        $tmtLama = Carbon::parse($pegawai->tmt_kgb_lama)->translatedFormat('d F Y');
        $tmtBaru = $referenceDate->translatedFormat('d F Y');
        /*
        |--------------------------------------------------------------------------
        | Pesan notifikasi
        |--------------------------------------------------------------------------
        */
        $pesan = "Yth. Bapak/Ibu {$pegawai->nama},\n\n"
            . "Kenaikan Gaji Berkala (KGB) Anda telah berhasil diproses "
            . "sesuai dengan ketentuan yang berlaku.\n\n"
            . "Informasi KGB:\n"
            . "- TMT KGB Sebelumnya : {$tmtLama}\n"
            . "- TMT KGB Berikutnya : {$tmtBaru}\n"
            . "- Gaji Pokok Baru : Rp "
            . number_format($gajiBaru, 0, ',', '.')
            . "\n\n"
            . "Silakan menyimpan Surat Keputusan (SK) "
            . "Kenaikan Gaji Berkala sebagai arsip kepegawaian.\n\n"
            . "Terima kasih.";
        /*
        |--------------------------------------------------------------------------
        | Cek notifikasi yang sudah ada
        |
        | Unik berdasarkan:
        | - NIP
        | - Jenis notifikasi
        | - TMT KGB
        |--------------------------------------------------------------------------
        */
        $notification = $this->notification_repository->findNotification( $pegawai->nip, 'KGB Diproses', $referenceDate->toDateString());
        /*
        |--------------------------------------------------------------------------
        | Jika sudah pernah dibuat, jangan buat lagi
        |--------------------------------------------------------------------------
        */
        if ($notification) {
            return;
        }
        /*
        |--------------------------------------------------------------------------
        | Buat notifikasi baru
        |--------------------------------------------------------------------------
        */
        $notification = $this->notification_repository->create([
            'nip'                       => $pegawai->nip,
            'jenis'                      => 'KGB Diproses',
            'judul'                     => 'Kenaikan Gaji Berkala Telah Diproses',
            'pesan'                   => $pesan,
            'tanggal_notifikasi'         => now(),
            'tanggal_referensi'            => $referenceDate->toDateString(),
            'sudah_dibaca'                   => false,
            // Status pengiriman pegawai
            'email_terkirim'                => false,
            'whatsapp_terkirim'             => false,
            // Status pengiriman Super Admin
            'email_super_admin_terkirim'    => false,
            'whatsapp_super_admin_terkirim' => false,
        ]);
        /*
        |--------------------------------------------------------------------------
        | Load employee untuk kebutuhan pengiriman
        |--------------------------------------------------------------------------
        */
        $notification->load('employee');
        /*
        |--------------------------------------------------------------------------
        | Kirim notifikasi
        |--------------------------------------------------------------------------
        */
        $this->dispatchNotification($notification);
    }
    /*
    |--------------------------------------------------------------------------
    | Create Notification
    |--------------------------------------------------------------------------
    */
    public function createNotification(Employee $pegawai, string $jenis, string $judul, string $pesan, Carbon $referenceDate) : Notification{
        /*
        |--------------------------------------------------------------------------
        | Jika notifikasi sudah ada
        |--------------------------------------------------------------------------
        */
        $notification = $this->notification_repository->findNotification( $pegawai->nip, $jenis, $referenceDate);
        if ($notification) {
            $notification->update([
                'judul' => $judul,
                'pesan' => $pesan,
                'tanggal_referensi' => $referenceDate->toDateString(),
            ]);
            $notification->load('employee');
            $this->dispatchNotification($notification);
            return $notification;
        }
        $notification = $this->notification_repository->create([
            'nip' => $pegawai->nip,
            'jenis' => $jenis,
            'judul' => $judul,
            'pesan' => $pesan,
            'tanggal_notifikasi' => now()->toDateString(),
            'tanggal_referensi' => $referenceDate->toDateString(),
            'sudah_dibaca' => false,
            'email_terkirim' => false,
            'whatsapp_terkirim' => false,
            'email_super_admin_terkirim' => false,
            'whatsapp_super_admin_terkirim' => false,
        ]);
        $notification->load('employee');
        $this->dispatchNotification($notification);
        return $notification;
    }
    private function dispatchNotification($notification, bool $sendToSuperAdmin = true): void
    {
        if( $notification->email_terkirim && $notification->whatsapp_terkirim &&
            (!$sendToSuperAdmin ||( $notification->email_super_admin_terkirim && $notification->whatsapp_super_admin_terkirim))) {
            // Log::info('Notification sudah selesai dikirim.', [
            //     'notification_id' => $notification->id,
            // ]);
            return;
        }
        /*
        |--------------------------------------------------------------------------
        | Delay Queue
        |--------------------------------------------------------------------------
        */
        $emailDelay = $this->getNextEmailDelay();
        $waDelay    = $this->getNextWhatsappDelay();
        // Log::info('Notification queued', [
        //     'notification_id' => $notification->id,
        //     'email_delay'     => $emailDelay,
        //     'wa_delay'        => $waDelay,
        // ]);
        /*
        |--------------------------------------------------------------------------
        | EMAIL PEGAWAI
        |--------------------------------------------------------------------------
        */
        if ( !$notification->email_terkirim && $notification->employee?->email) {
            // Log::info('MASUK EMAIL PEGAWAI');
            SendEmailJob::dispatch($notification,$notification->employee->email)->delay(now()->addSeconds($emailDelay));
        }
        /*
        |--------------------------------------------------------------------------
        | WHATSAPP PEGAWAI
        |--------------------------------------------------------------------------
        */
        if ( !$notification->whatsapp_terkirim && $notification->employee?->no_hp) {
            // Log::info('MASUK WHATSAPP PEGAWAI');
            SendWhatsappJob::dispatch($notification,$notification->employee->no_hp)->delay(now()->addSeconds($waDelay));
        }
        /*
        |--------------------------------------------------------------------------
        | Copy ke Super Admin
        |--------------------------------------------------------------------------
        */
        if ($sendToSuperAdmin) {
            $this->sendToSuperAdmin( $notification, $emailDelay + 30, $waDelay + 60);
        }
    }
    public function resendPendingNotifications(Employee $pegawai): void
    {
        $batasTanggal = now()->subYears(self::RESEND_NOTIFICATION_YEARS);
        $notifications = $pegawai->notifications()->whereNotNull('tanggal_referensi')
            ->where(function ($query) use ($batasTanggal) {
                // Notifikasi 5 tahun terakhir
                $query->whereDate('tanggal_referensi', '>=', $batasTanggal)
                    // ATAU notifikasi yang belum terkirim
                    ->orWhere(function ($query) {
                        $query->where('email_terkirim', false)->orWhere('whatsapp_terkirim', false);
                    });
            })->orderBy('tanggal_referensi')->get();
        // Log::info('Resend notification dimulai.', [
        //     'nip' => $pegawai->nip,
        //     'jumlah'      => $notifications->count(),
        // ]);
        foreach ($notifications as $notification) {
            $this->dispatchNotification( $notification, false);
        }
        // Log::info('Resend notification selesai.', [
        //     'nip' => $pegawai->nip,
        // ]);
    }
    private function sendToSuperAdmin( $notification, int $emailDelay = 0, int $waDelay = 0): void{
        $admin = $this->getSuperAdmin();
        if (!$admin) {
            return;
        }
        /*
        |--------------------------------------------------------------------------
        | EMAIL SUPER ADMIN
        |--------------------------------------------------------------------------
        */
        if ($admin->email && !$notification->email_super_admin_terkirim) {
            SendEmailJob::dispatch( $notification, $admin->email, true)->delay(now()->addSeconds($emailDelay));
            // Log::info('Email super admin masuk queue', [
            //     'admin_id'        => $admin->id,
            //     'notification_id' => $notification->id,
            //     'delay'           => $emailDelay,
            // ]);
        }
        /*
        |--------------------------------------------------------------------------
        | WHATSAPP SUPER ADMIN
        |--------------------------------------------------------------------------
        */
        if ($admin->no_hp && !$notification->whatsapp_super_admin_terkirim) {
            SendWhatsappJob::dispatch( $notification, $admin->no_hp, true)->delay(now()->addSeconds($waDelay));
            // Log::info('WA super admin masuk queue', [
            //     'admin_id'        => $admin->id,
            //     'notification_id' => $notification->id,
            //     'delay'           => $waDelay,
            // ]);
        }
    }
    private function getSuperAdmin(): ?User
    {
        if (!$this->superAdmin) {
            $this->superAdmin = User::where('role','super_admin')->first();
        }
        return $this->superAdmin;
    }
    private function getNextWhatsappDelay(): int
    {
        return Cache::lock('wa_delay_lock', 10)->block(5, function () {
            $key = self::WA_CACHE_KEY;
            $timestamp = Cache::get($key);
            if (!$timestamp) {
                $delay = 0;
                $next = now()->addSeconds(self::WA_DELAY);
            } else {
                $next = Carbon::createFromTimestamp((int) $timestamp);
                $delay = max(0, now()->diffInSeconds($next, false));
                $next->addSeconds(self::WA_DELAY);
            }
            Cache::put($key, $next->timestamp, now()->addDay());
            return $delay;
        });
    }
    private function getNextEmailDelay(): int
    {
        return Cache::lock('email_delay_lock', 10)->block(5, function () {
            $key = self::EMAIL_CACHE_KEY;
            $timestamp = Cache::get($key);
            if (!$timestamp) {
                $delay = 0;
                $next = now()->addSeconds(self::EMAIL_DELAY);
            } else {
                $next = Carbon::createFromTimestamp((int) $timestamp);
                $delay = max(0, now()->diffInSeconds($next, false));
                $next->addSeconds(self::EMAIL_DELAY);
            }
            Cache::put($key, $next->timestamp, now()->addDay());
            return $delay;
        });
    }
    private function getAuthenticatedUser(): User
    {
        return Auth::user() ?? abort(401);
    }

    private function getUserNip(User $user): string
    {
        abort_unless(
            $user->role === 'user' && filled($user->nip),
            403,
            'Akun tidak terhubung dengan data pegawai.'
        );

        return $user->nip;
    }

    private function getAdminUnit(User $user): string
    {
        abort_unless(
            $user->role === 'admin' && filled($user->kode_unit_kerja),
            403,
            'Akun admin belum memiliki unit kerja.'
        );

        return $user->kode_unit_kerja;
    }
}

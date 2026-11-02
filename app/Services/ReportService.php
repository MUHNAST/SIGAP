<?php
namespace App\Services;
use App\Models\Employee;
use App\Models\KgbHistory;
use App\Models\Salary;
use App\Models\User;
use App\Repositories\ReportRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class ReportService
{
    protected ReportRepository $repository;
    public function __construct(ReportRepository $repository)
    {
        $this->repository = $repository;
    }
    private function authenticatedUser(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        return $user;
    }
    public function getLaporanKGB(array $filter = []): Collection
    {
        $user = $this->authenticatedUser();
        return $this->repository
            ->getLaporanKGB($filter, $user)
            ->map(function ($employee) {
                return $this->prepareKgbInformation($employee);
            });
    }
    public function getEmployeeForKgbPreview(string $nip): Employee
    {
        $user = $this->authenticatedUser();
        $employee = $this->repository->getEmployeeForKgbPreview(
            $nip,
            $user
        );
         $this->ensureKgbPreviewAvailable($employee, $user);
        return $this->prepareKgbInformation($employee);
    }
    // Memastikan periode KGB memenuhi ketentuan preview untuk seluruh role.
    private function ensureKgbPreviewAvailable(
        Employee $employee,
        User $user
    ): void {
        // Memastikan pegawai merupakan PNS.
        abort_unless(
            $employee->status_pegawai === 'PNS',
            403,
            'Preview surat KGB hanya tersedia untuk pegawai PNS.'
        );

        // Memastikan TMT KGB tersedia.
        abort_unless(
            filled($employee->tmt_kgb_baru),
            403,
            'TMT KGB belum tersedia.'
        );

        $tmtKgb = Carbon::parse(
            $employee->tmt_kgb_baru
        )->startOfDay();

        // Memastikan periode KGB belum pernah diproses.
        $existingHistory = $this->repository->getExistingKgbHistory(
            $employee->nip,
            $tmtKgb,
            $user
        );

        abort_if(
            $existingHistory !== null,
            403,
            'Periode KGB ini sudah pernah diproses.'
        );

        // Memastikan tanggal preview berada dalam rentang yang ditentukan.
        $today = Carbon::today();

        $awalPeriode = $tmtKgb
            ->copy()
            ->subMonthsNoOverflow(2);

        abort_if(
            $today->lt($awalPeriode) || $today->gt($tmtKgb),
            403,
            'Preview hanya tersedia mulai dua bulan sebelum TMT KGB sampai dengan tanggal TMT KGB.'
        );
    }
    public function getEmployee(string $nip): Employee
    {
        $user = $this->authenticatedUser();
        $employee = $this->repository->getEmployeeByNip(
            $nip,
            $user
        );
        return $this->prepareKgbInformation($employee);
    }
    private function rupiahToInt(?string $value): int
    {
        return (int) preg_replace('/[^0-9]/', '', $value ?? '0');
    }
    public function getHistory(): Collection
    {
        $user = $this->authenticatedUser();
        return $this->repository->getHistory($user);
    }
    public function getHistoryById(int $id): KgbHistory
    {
        $user = $this->authenticatedUser();
        $history = $this->repository->getHistoryById($id, $user);
        $history->setRelation(
            'employee',
            $this->prepareKgbInformation(
                $history->employee,
                $history
            )
        );
        return $history;
    }
    public function getCamat(): ?Employee
    {
        return $this->repository->getCamat();
    }
    // Menyiapkan informasi jadwal, masa kerja, dan gaji KGB.
    private function prepareKgbInformation(
        Employee $employee,
        ?KgbHistory $history = null
    ): Employee {
        $today = Carbon::today();
        $oldTMT = $history?->tmt_kgb_lama
            ?? $employee->tmt_kgb_lama;
        $newTMT = $history?->tmt_kgb_baru
            ?? $employee->tmt_kgb_baru;
        if (! $oldTMT || ! $newTMT) {
            $employee->setAttribute('tanggal_kgb', null);
            $employee->setAttribute('hari', null);
            $employee->setAttribute('tahun_sisa', 0);
            $employee->setAttribute('bulan_sisa', 0);
            $employee->setAttribute('hari_sisa', 0);
            $employee->setAttribute('progress_kgb', 0);
            $employee->setAttribute('status_kgb', 'Belum tersedia');
            $employee->setAttribute('badge_kgb', 'bg-slate-500');
            return $employee;
        }
        $oldTMT = Carbon::parse($oldTMT)->startOfDay();
        $newTMT = Carbon::parse($newTMT)->startOfDay();
        /*
        |--------------------------------------------------------------------------
        | Sisa hari dan status KGB
        |--------------------------------------------------------------------------
        */
        $hari = (int) $today->diffInDays($newTMT, false);
        if ($hari === 0) {
            $status = 'Hari Ini';
            $badge = 'bg-green-500';
        } elseif ($hari < 0) {
            $status = 'Terlambat';
            $badge = 'bg-red-500';
        } else {
            $status = 'Menunggu';
            $badge = 'bg-blue-500';
        }
        /*
        |--------------------------------------------------------------------------
        | Sisa waktu
        |--------------------------------------------------------------------------
        */
        if ($hari <= 0) {
            $tahunSisa = 0;
            $bulanSisa = 0;
            $hariSisa = 0;
        } else {
            $interval = $today->diff($newTMT);
            $tahunSisa = $interval->y;
            $bulanSisa = $interval->m;
            $hariSisa = $interval->d;
        }
        /*
        |--------------------------------------------------------------------------
        | Progress KGB
        |--------------------------------------------------------------------------
        */
        $totalHari = max(
            (int) $oldTMT->diffInDays($newTMT),
            1
        );
        if ($today->lte($oldTMT)) {
            $progress = 0;
        } elseif ($today->gte($newTMT)) {
            $progress = 100;
        } else {
            $progress = (int) round(
                (
                    $oldTMT->diffInDays($today)
                    / $totalHari
                ) * 100
            );
        }
        /*
        |--------------------------------------------------------------------------
        | Pangkat
        |--------------------------------------------------------------------------
        */
        $idPangkat = $history?->id_pangkat
            ?? $employee->id_pangkat;
        /*
        |--------------------------------------------------------------------------
        | Masa kerja lama
        |--------------------------------------------------------------------------
        */
        $masaKerjaTahunLama = (int) (
            $history?->tahun_masa_kerja_lama
            ?? $employee->tahun_masa_kerja
        );
        $masaKerjaBulanLama = (int) (
            $history?->bulan_masa_kerja_lama
            ?? $employee->bulan_masa_kerja
        );
        /*
        |--------------------------------------------------------------------------
        | Masa kerja baru
        |--------------------------------------------------------------------------
        |
        | Riwayat:
        | Gunakan data yang sudah tersimpan.
        |
        | PPPK:
        | Gunakan masa kerja dari data pegawai.
        |
        | PNS:
        | Untuk KGB yang belum diproses, masa kerja baru dihitung +2 tahun.
        |--------------------------------------------------------------------------
        */
        if ($history) {
            $masaKerjaTahunBaru = (int) (
                $history->tahun_masa_kerja_baru
            );
            $masaKerjaBulanBaru = (int) (
                $history->bulan_masa_kerja_baru
            );
        } elseif ($employee->status_pegawai === 'PPPK') {
            $masaKerjaTahunBaru = $masaKerjaTahunLama;
            $masaKerjaBulanBaru = $masaKerjaBulanLama;
        } else {
            $masaKerjaTahunBaru = $masaKerjaTahunLama + 2;
            $masaKerjaBulanBaru = $masaKerjaBulanLama;
        }
        /*
        |--------------------------------------------------------------------------
        | Gaji lama
        |--------------------------------------------------------------------------
        */
        if ($history && $history->gaji_pokok_lama !== null) {
            $gajiLama = (object) [
                'gaji_pokok' => $history->gaji_pokok_lama,
            ];
        } else {
            $gajiLama = Salary::query()
                ->where('id_pangkat', $idPangkat)
                ->where(
                    'tahun_masa_kerja',
                    '<=',
                    $masaKerjaTahunLama
                )
                ->orderByDesc('tahun_masa_kerja')
                ->first();
        }
        /*
        |--------------------------------------------------------------------------
        | Gaji baru
        |--------------------------------------------------------------------------
        */
        if ($history && $history->gaji_pokok_baru !== null) {
            $gajiBaru = (object) [
                'gaji_pokok' => $history->gaji_pokok_baru,
            ];
        } else {
            $gajiBaru = Salary::query()
                ->where('id_pangkat', $idPangkat)
                ->where(
                    'tahun_masa_kerja',
                    '<=',
                    $masaKerjaTahunBaru
                )
                ->orderByDesc('tahun_masa_kerja')
                ->first();
        }
        $gajiLama ??= $gajiBaru;
        /*
        |--------------------------------------------------------------------------
        | Attribute tambahan untuk laporan
        |--------------------------------------------------------------------------
        */
        $employee->setAttribute('tanggal_kgb', $newTMT);
        $employee->setAttribute('hari', $hari);
        $employee->setAttribute('status_kgb', $status);
        $employee->setAttribute('badge_kgb', $badge);
        $employee->setAttribute('tahun_sisa', $tahunSisa);
        $employee->setAttribute('bulan_sisa', $bulanSisa);
        $employee->setAttribute('hari_sisa', $hariSisa);
        $employee->setAttribute('progress_kgb', $progress);
        $employee->setAttribute(
            'tahun_masa_kerja_lama',
            $masaKerjaTahunLama
        );
        $employee->setAttribute(
            'bulan_masa_kerja_lama',
            $masaKerjaBulanLama
        );
        $employee->setAttribute(
            'tahun_masa_kerja_baru',
            $masaKerjaTahunBaru
        );
        $employee->setAttribute(
            'bulan_masa_kerja_baru',
            $masaKerjaBulanBaru
        );
        $employee->setAttribute('gaji_pokok_lama', $gajiLama);
        $employee->setAttribute('gaji_pokok_baru', $gajiBaru);
        return $employee;
    }
    // Menyiapkan data untuk surat KGB.
    // public function prepareSuratData(
    //     Employee $employee,
    //     ?KgbHistory $history = null,
    //     bool $isHistory = false
    // ): object {
    //     $camat = $this->getCamat();
    //     $employee = $this->prepareKgbInformation(
    //         $employee,
    //         $history
    //     );
    //     /*
    //     |--------------------------------------------------------------------------
    //     | Dasar SK gaji lama
    //     |--------------------------------------------------------------------------
    //     */
    //     if ($isHistory) {
    //         $OldOfficial = $history?->pejabat;
    //         $tanggalSkSebelumnya = $history?->tanggal_sk_lama;
    //         $nomorSkSebelumnya = $history?->nomor_sk_lama;
    //     } else {
    //         $dasarSk = $this->getPreviousHistory(
    //             $employee,
    //             $history
    //         );
    //         $OldOfficial = $dasarSk?->pejabat
    //             ?: $camat?->position?->nama
    //             ?: 'Camat Pekalongan Timur';
    //         $tanggalSkSebelumnya = $dasarSk?->tanggal_sk_baru;
    //         $nomorSkSebelumnya = $dasarSk?->nomor_sk_baru;
    //     }
    //     return (object) [
    //         'nomor_sk_baru' => $history?->nomor_sk_baru
    //             ?: 'B/ - /' . now()->year,
    //         'tanggal_sk_baru' => $history?->tanggal_sk_baru
    //             ?? now(),
    //         'pejabat' => 'Camat Pekalongan Timur',
    //         'old_official' => $OldOfficial,
    //         'tanggal_sk_sebelumnya' => $tanggalSkSebelumnya,
    //         'nomor_sk_sebelumnya' => $nomorSkSebelumnya,
    //         'nomor_sk_lama' => $isHistory
    //             ? ($history?->nomor_sk_lama ?: '-')
    //             : ($nomorSkSebelumnya ?: '-'),
    //         'tanggal_sk_lama' => $isHistory
    //             ? $history?->tanggal_sk_lama
    //             : $tanggalSkSebelumnya,
    //         'tmt_kgb_lama' => $history?->tmt_kgb_lama
    //             ?? $employee->tmt_kgb_lama,
    //         'tmt_kgb_baru' => $history?->tmt_kgb_baru
    //             ?? $employee->tmt_kgb_baru,
    //         'tahun_masa_kerja_lama' =>
    //             $employee->tahun_masa_kerja_lama,
    //         'bulan_masa_kerja_lama' =>
    //             $employee->bulan_masa_kerja_lama,
    //         'tahun_masa_kerja_baru' =>
    //             $employee->tahun_masa_kerja_baru,
    //         'bulan_masa_kerja_baru' =>
    //             $employee->bulan_masa_kerja_baru,
    //         'gaji_pokok_lama' =>
    //             optional($employee->gaji_pokok_lama)->gaji_pokok ?? 0,
    //         'gaji_pokok_baru' =>
    //             optional($employee->gaji_pokok_baru)->gaji_pokok ?? 0,
    //         'dasar_hukum_kgb' => $history?->dasar_hukum_kgb
    //             ?? 'Pasal 1 Peraturan Pemerintah Republik Indonesia Nomor : 5 Tahun 2024',
    //     ];
    // }
    // Menyiapkan data untuk surat KGB.
    public function prepareSuratData(
        Employee $employee,
        ?KgbHistory $history = null,
        bool $isHistory = false
    ): object {
        $camat = $this->getCamat();
        $employee = $this->prepareKgbInformation(
            $employee,
            $history
        );
        /*
        |--------------------------------------------------------------------------
        | Mengambil dasar SK gaji lama
        |--------------------------------------------------------------------------
        |
        | Cetak KGB baru:
        | Mengambil nomor dan tanggal SK baru dari riwayat KGB sebelumnya.
        |
        | Cetak riwayat:
        | Menggunakan data SK lama yang tersimpan pada riwayat tersebut.
        |
        */
        $dasarSk = null;
        if (! $isHistory) {
            $dasarSk = $this->getPreviousHistory(
                $employee,
                $history
            );
        }
        /*
        |--------------------------------------------------------------------------
        | Nomor dan tanggal SK lama
        |--------------------------------------------------------------------------
        */
        if ($isHistory) {
            // Cetak ulang riwayat KGB yang sudah tersimpan.
            $nomorSkLama = $history?->nomor_sk_lama;
            $tanggalSkLama = $history?->tanggal_sk_lama;
        } else {
            // Cetak KGB baru menggunakan SK baru dari riwayat sebelumnya.
            $nomorSkLama = $dasarSk?->nomor_sk_baru;
            $tanggalSkLama = $dasarSk?->tanggal_sk_baru;
        }
        /*
        |--------------------------------------------------------------------------
        | Pejabat yang menetapkan SK sebelumnya
        |--------------------------------------------------------------------------
        */
        $oldOfficial = $isHistory
            ? (
                $history?->pejabat
                ?: $camat?->position?->nama
                ?: 'Camat Pekalongan Timur'
            )
            : (
                $dasarSk?->pejabat
                ?: $camat?->position?->nama
                ?: 'Camat Pekalongan Timur'
            );
        return (object) [
            // SK baru
            'nomor_sk_baru' => $history?->nomor_sk_baru
                ?: 'B/ - /' . now()->year,
            'tanggal_sk_baru' => $history?->tanggal_sk_baru
                ?? now(),
            // Pejabat
            'pejabat' => 'Camat Pekalongan Timur',
            'old_official' => $oldOfficial,
            // SK lama
            'tanggal_sk_sebelumnya' => $tanggalSkLama,
            'nomor_sk_sebelumnya' => $nomorSkLama,
            'nomor_sk_lama' => $nomorSkLama ?: '-',
            'tanggal_sk_lama' => $tanggalSkLama,
            // Periode KGB
            'tmt_kgb_lama' => $history?->tmt_kgb_lama
                ?? $employee->tmt_kgb_lama,
            'tmt_kgb_baru' => $history?->tmt_kgb_baru
                ?? $employee->tmt_kgb_baru,
            // Masa kerja
            'tahun_masa_kerja_lama' =>
                $employee->tahun_masa_kerja_lama,
            'bulan_masa_kerja_lama' =>
                $employee->bulan_masa_kerja_lama,
            'tahun_masa_kerja_baru' =>
                $employee->tahun_masa_kerja_baru,
            'bulan_masa_kerja_baru' =>
                $employee->bulan_masa_kerja_baru,
            // Gaji
            'gaji_pokok_lama' =>
                optional($employee->gaji_pokok_lama)->gaji_pokok ?? 0,
            'gaji_pokok_baru' =>
                optional($employee->gaji_pokok_baru)->gaji_pokok ?? 0,
            // Dasar hukum
            'dasar_hukum_kgb' => $history?->dasar_hukum_kgb
                ?? 'Pasal 1 Peraturan Pemerintah Republik Indonesia Nomor : 5 Tahun 2024',
        ];
    }
    // Memproses cetak KGB dan menyimpan riwayat.
    public function printKgb(
        string $nip,
        array $data = []
    ): ?KgbHistory {
        $user = $this->authenticatedUser();
        $employee = $this->repository->getEmployeeByNip(
            $nip,
            $user
        );
         // Validasi ulang status, TMT, riwayat, dan periode preview.
        $this->ensureKgbPreviewAvailable($employee, $user);
        $employee = $this->prepareKgbInformation($employee);
        $created = false;
        $history = null;
        DB::transaction(function () use (
            $employee,
            $data,
            $user,
            &$created,
            &$history
        ) {
            /*
            |--------------------------------------------------------------------------
            | Cek periode KGB yang sudah diproses
            |--------------------------------------------------------------------------
            */
            $history = KgbHistory::query()
                ->where('nip', $employee->nip)
                ->whereDate(
                    'tmt_kgb_baru',
                    $employee->tmt_kgb_baru
                )
                ->lockForUpdate()
                ->first();
            if ($history) {
                abort(
                    403,
                    'Periode KGB ini sudah pernah diproses.'
                );
            }
            /*
            |--------------------------------------------------------------------------
            | Tanggal SK baru
            |--------------------------------------------------------------------------
            */
            $tanggalSk = Carbon::now()->toDateString();
            if (! empty($data['tanggal_sk_baru'])) {
                Carbon::setLocale('id');
                try {
                    $tanggalSk = Carbon::createFromLocaleFormat(
                        'd F Y',
                        'id',
                        trim($data['tanggal_sk_baru'])
                    )->toDateString();
                } catch (\Throwable $e) {
                    $tanggalSk = Carbon::parse(
                        $data['tanggal_sk_baru']
                    )->toDateString();
                }
            }
            /*
            |--------------------------------------------------------------------------
            | Tanggal SK lama
            |--------------------------------------------------------------------------
            */
            $tanggalSkLama = null;
            if (! empty($data['tanggal_sk_lama'])) {
                Carbon::setLocale('id');
                try {
                    $tanggalSkLama = Carbon::createFromLocaleFormat(
                        'd F Y',
                        'id',
                        trim($data['tanggal_sk_lama'])
                    )->toDateString();
                } catch (\Throwable $e) {
                    $tanggalSkLama = Carbon::parse(
                        $data['tanggal_sk_lama']
                    )->toDateString();
                }
            }
            /*
            |--------------------------------------------------------------------------
            | Masa kerja dihitung dari data pegawai di server
            |--------------------------------------------------------------------------
            */
            $tahunMasaKerjaLama = (int) ( $employee->tahun_masa_kerja);
            $bulanMasaKerjaLama = (int) ( $employee->bulan_masa_kerja);
            $tahunMasaKerjaBaru = $tahunMasaKerjaLama + 2;
            $bulanMasaKerjaBaru = $bulanMasaKerjaLama;
            /*
            |--------------------------------------------------------------------------
            | Periode KGB berasal dari data pegawai, bukan formulir
            |--------------------------------------------------------------------------
            */
            $tmtKgbLama = $employee->tmt_kgb_lama;
            $tmtKgbBaru = Carbon::parse(
                $employee->tmt_kgb_baru
            )->toDateString();
            /*
            |--------------------------------------------------------------------------
            | Simpan riwayat KGB
            |--------------------------------------------------------------------------
            */
            $history = KgbHistory::create([
                'nip' => $employee->nip,
                'id_pangkat' => $employee->id_pangkat,
                'nomor_sk_lama' => $data['nomor_sk_lama'] ?? null,
                'nomor_sk_baru' => trim( $data['nomor_sk_baru'] ?? ''),
                'tanggal_sk_lama' => $tanggalSkLama,
                'tanggal_sk_baru' => $tanggalSk,
                'pejabat' => trim( $data['pejabat'] ?? ''),
                'tmt_kgb_lama' => $tmtKgbLama,
                'tmt_kgb_baru' => $tmtKgbBaru,
                'tahun_masa_kerja_lama' => $tahunMasaKerjaLama,
                'bulan_masa_kerja_lama' => $bulanMasaKerjaLama,
                'tahun_masa_kerja_baru' => $tahunMasaKerjaBaru,
                'bulan_masa_kerja_baru' => $bulanMasaKerjaBaru,
                'gaji_pokok_lama' => $this->rupiahToInt( (string) ($employee->gaji_pokok_lama->gaji_pokok ?? 0)),
                'gaji_pokok_baru' => $this->rupiahToInt( (string) ($employee->gaji_pokok_baru->gaji_pokok ?? 0)),
                'dasar_hukum_kgb' => trim( $data['dasar_hukum_kgb'] ?? 'Pasal 1 Peraturan Pemerintah Republik Indonesia Nomor : 5 Tahun 2024'),
                'id_user' => $user->id,
            ]);
            /*
            |--------------------------------------------------------------------------
            | Perbarui data pegawai setelah riwayat berhasil dibuat
            |--------------------------------------------------------------------------
            */
            Employee::query()
                ->where('nip', $employee->nip)
                ->update([
                    'tmt_kgb_lama' => $tmtKgbBaru,
                    'tmt_kgb_baru' => Carbon::parse($tmtKgbBaru)
                        ->addYearsNoOverflow(2)
                        ->toDateString(),
                    'tahun_masa_kerja' => $tahunMasaKerjaBaru,
                    'bulan_masa_kerja' => $bulanMasaKerjaBaru,
                ]);
            $created = true;
        });
        /*
        |--------------------------------------------------------------------------
        | Notifikasi setelah transaksi berhasil
        |--------------------------------------------------------------------------
        */
        if ($created) {
             if (!$history instanceof KgbHistory) {
                throw new \RuntimeException('Data riwayat KGB tidak ditemukan setelah proses penyimpanan.');
            }
            $freshEmployee = $employee->fresh();
            if ($freshEmployee instanceof Employee) {
                app(NotificationService::class)->notifyKgbProcessed(
                    $freshEmployee,
                    $this->rupiahToInt(
                        (string) $history->gaji_pokok_baru
                    )
                );
            }
        }
        return $history?->fresh(['employee']);
    }
    public function getExistingKgbHistory(
        string $nip
    ): ?KgbHistory {
        $user = $this->authenticatedUser();
        $employee = $this->repository->getEmployeeByNip(
            $nip,
            $user
        );
        if (! $employee->tmt_kgb_baru) {
            return null;
        }
        return $this->repository->getExistingKgbHistory(
            $employee->nip,
            $employee->tmt_kgb_baru,
            $user
        );
    }
    // Mengambil riwayat sebelumnya dengan pembatasan akses.
    public function getPreviousHistory(
        Employee $employee,
        ?KgbHistory $currentHistory = null
    ): ?KgbHistory {
        $user = $this->authenticatedUser();
        $authorizedEmployee = $this->repository->getEmployeeByNip(
            $employee->nip,
            $user
        );
        $beforeTmt = $currentHistory?->tmt_kgb_baru
            ?? $authorizedEmployee->tmt_kgb_baru;
        if (! $beforeTmt) {
            return null;
        }
        return $this->repository->getPreviousHistory(
            $authorizedEmployee->nip,
            $user,
            Carbon::parse($beforeTmt)->toDateString(),
            $currentHistory?->id
        );
    }
    // Memastikan akun merupakan User dan memiliki NIP.
    private function authenticatedEmployeeUser(): User
    {
        $user = $this->authenticatedUser();
        abort_unless(
            $user->role === 'user',
            403,
            'Fitur ini hanya dapat diakses oleh User.'
        );
        abort_unless(
            filled($user->nip),
            403,
            'Akun Anda belum terhubung dengan data pegawai.'
        );
        return $user;
    }
    // Memastikan NIP yang diminta adalah NIP milik akun User.
    private function ensureOwnEmployee(string $nip): User
    {
        $user = $this->authenticatedEmployeeUser();
        abort_unless(
            (string) $user->nip === (string) $nip,
            403,
            'Anda tidak memiliki akses ke data pegawai tersebut.'
        );
        return $user;
    }
    // Mengambil laporan KGB khusus User.
    public function getLaporanKGBForUser(
        array $filter = []
    ): Collection {
        $user = $this->authenticatedEmployeeUser();

        return $this->repository
            ->getLaporanKGBForUser($user, $filter)
            ->map(function (Employee $employee) {
                return $this->prepareKgbInformation($employee);
            });
    }
    // Mengambil data pegawai untuk preview KGB khusus User.
    public function getEmployeeForUserKgbPreview(string $nip): Employee
    {
        $user = $this->ensureOwnEmployee($nip);
        $employee = $this->repository->getEmployeeByNip(
            $nip,
            $user
        );
        $this->ensureUserKgbPreviewAvailable($employee);
        return $this->prepareKgbInformation($employee);
    }
    // Memastikan periode KGB memenuhi ketentuan preview.
    public function ensureUserKgbPreviewAvailable(
        Employee $employee
    ): void {
        $user = $this->authenticatedEmployeeUser();

        // Memastikan pegawai merupakan milik akun User.
        abort_unless(
            (string) $employee->nip === (string) $user->nip,
            403,
            'Anda tidak memiliki akses ke data pegawai tersebut.'
        );

        // Menggunakan validasi periode KGB yang sama.
        $this->ensureKgbPreviewAvailable($employee, $user);
    }
    // Memproses cetak KGB khusus User.
    public function printKgbForUser(
        string $nip,
        array $data = []
    ): ?KgbHistory {
        $this->ensureOwnEmployee($nip);
        // Validasi ulang kepemilikan, status PNS, dan periode KGB.
        $this->getEmployeeForUserKgbPreview($nip);
        // Gunakan proses cetak utama agar penyimpanan riwayat,
        // pembaruan TMT, dan notifikasi tetap berjalan.
        return $this->printKgb($nip, $data);
    }
}

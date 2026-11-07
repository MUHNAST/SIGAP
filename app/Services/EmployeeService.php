<?php
namespace App\Services;
use App\Exports\EmployeeExport;
use App\Exports\EmployeeTemplateExport;
use App\Models\Employee;
use App\Repositories\EmployeeRepository;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use App\Imports\EmployeeImport;
use App\Models\PromotionHistory;
use App\Models\Rank;
use App\Jobs\ResendNotificationJob;
use App\Models\Position;
use App\Models\Pppk;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use App\Models\EmployeeDocument;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class EmployeeService
{
    private const DEFAULT_PHOTO = 'images/default-user.png';
    private const RESEND_NOTIFICATION_YEARS = 5;
    private EmployeeRepository $employee_repository;
    private EmployeeImport $employeeImport;
    protected EmployeeUserSyncService $employeeUserSyncService;
    private array $nextRank = [
        'I/a'   => 'I/b',
        'I/b'   => 'I/c',
        'I/c'   => 'I/d',
        'I/d'   => 'II/a',
        'II/a'  => 'II/b',
        'II/b'  => 'II/c',
        'II/c'  => 'II/d',
        'II/d'  => 'III/a',
        'III/a' => 'III/b',
        'III/b' => 'III/c',
        'III/c' => 'III/d',
        'III/d' => 'IV/a',
        'IV/a'  => 'IV/b',
        'IV/b'  => 'IV/c',
        'IV/c'  => 'IV/d',
        'IV/d'  => 'IV/e',
        'IV/e'  => null,
    ];
    public function __construct(
        EmployeeRepository $employee_repository,
        EmployeeImport $employeeImport,
        EmployeeUserSyncService $employeeUserSyncService
    ) {
        $this->employee_repository = $employee_repository;
        $this->employeeImport = $employeeImport;
        $this->employeeUserSyncService = $employeeUserSyncService;
    }
    public function getAll(User $user, array $data = ['*'])
    {
        return $this->employee_repository->getAll($user, $data);
    }
    public function getById(string $nip, array $data = ['*'])
    {
         $user = Auth::user();
        abort_unless($user, 401);
        return $this->employee_repository->getById($nip, $user, $data);
    }
    public function create(array $data): Employee
    {
        $user = $this->authorizeEmployeeManagement();
        if ($user->role === 'admin') {
            $data['kode_unit_kerja'] = $user->kode_unit_kerja;
        }
        $documents = $data['dokumen'] ?? [];
        unset($data['dokumen']);
        $data = $this->prepareEmployeeData($data);
        $data = $this->handlePhoto($data);
        $newDocumentPaths = [];
        $oldDocumentPaths = [];
        try {
            $employee = DB::transaction(function () use (
                $data,
                $user,
                $documents,
                &$newDocumentPaths,
                &$oldDocumentPaths
            ) {
                // Simpan data pegawai.
                $employee = $this->employee_repository->create(
                    $data,
                    $user
                );
                // Buat akun pengguna berdasarkan NIP pegawai.
                $this->syncEmployeeUser($employee);
                // Simpan dokumen pegawai.
                $this->saveDocuments(
                    $employee,
                    $documents,
                    $newDocumentPaths,
                    $oldDocumentPaths
                );
                return $employee;
            });
        } catch (\Throwable $e) {
            $this->deleteStoredDocuments($newDocumentPaths);
            throw $e;
        }
        $this->deleteStoredDocuments($oldDocumentPaths);
        return $employee;
    }
    public function update(Employee $employee, array $data): Employee
    {
        $user = Auth::user();
        abort_unless($user, 401);
        /*
        |--------------------------------------------------------------------------
        | Validasi hak akses berdasarkan role
        |--------------------------------------------------------------------------
        */
        if ($user->role === 'user') {
            // User hanya dapat memperbarui data pegawai miliknya sendiri.
            abort_unless(
                filled($user->nip) &&
                (string) $user->nip === (string) $employee->nip,
                403,
                'Anda tidak memiliki hak akses untuk memperbarui data pegawai ini.'
            );
        } else {
            // Admin dan Super Admin dapat mengelola data pegawai.
            $user = $this->authorizeEmployeeManagement();
            // Memastikan Admin hanya mengelola pegawai pada unit kerjanya.
            $this->authorizeEmployee($employee);
        }
        $documents = $data['dokumen'] ?? [];
        unset($data['dokumen']);
        // User tidak diperbolehkan mengubah NIP melalui formulir data pribadi.
        if ($user->role === 'user') {
            unset($data['nip']);
        }
        $newDocumentPaths = [];
        $oldDocumentPaths = [];
        try {
            $result = DB::transaction(function () use (
                $employee,
                $data,
                $documents,
                $user,
                &$newDocumentPaths,
                &$oldDocumentPaths
            ) {
                $oldNip = $employee->nip;
                $oldPhone = $this->normalizePhone($employee->no_hp);
                $oldEmail = $employee->email;
                $oldRankId = $employee->id_pangkat;
                $oldStatusPegawai = $employee->status_pegawai;
                $oldTahunMasaKerja = (int) $employee->tahun_masa_kerja;
                $oldBulanMasaKerja = (int) $employee->bulan_masa_kerja;
                // Pertahankan tanggal masuk jika tidak dikirim.
                $data['tanggal_masuk'] = $data['tanggal_masuk']
                    ?? $employee->tanggal_masuk;
                // Persiapkan data pegawai.
                $data = $this->prepareEmployeeData($data);
                /*
                |--------------------------------------------------------------------------
                | Pembatasan unit kerja
                |--------------------------------------------------------------------------
                */
                if ($user->role === 'admin') {
                    // Admin hanya dapat mempertahankan unit kerja miliknya.
                    $data['kode_unit_kerja'] = $user->kode_unit_kerja;
                }
                // Super Admin dapat mengubah unit kerja tanpa pembatasan.
                // User dapat mengubah unit kerja pada data miliknya sendiri.
                /*
                |--------------------------------------------------------------------------
                | Deteksi perubahan kelompok pangkat PNS
                |--------------------------------------------------------------------------
                */
                $newStatusPegawai = $data['status_pegawai']
                    ?? $oldStatusPegawai;
                $newRankId = $data['id_pangkat'] ?? $oldRankId;
                $oldRank = $oldRankId
                    ? Rank::find($oldRankId)
                    : null;
                $newRank = $newRankId
                    ? Rank::find($newRankId)
                    : null;
                $groupChanged = false;
                if (
                    $oldStatusPegawai === 'PNS' &&
                    $newStatusPegawai === 'PNS' &&
                    $oldRank &&
                    $newRank
                ) {
                    $oldGroupCode = strtoupper(
                        explode('/', $oldRank->kode)[0]
                    );
                    $newGroupCode = strtoupper(
                        explode('/', $newRank->kode)[0]
                    );
                    $groupChanged = $oldGroupCode !== $newGroupCode;
                }
                /*
                |--------------------------------------------------------------------------
                | Penyesuaian masa kerja setelah perubahan kelompok pangkat
                |--------------------------------------------------------------------------
                */
                if ($groupChanged && $oldRank && $newRank) {
                    $tempEmployee = new Employee();
                    $tempEmployee->tahun_masa_kerja = $oldTahunMasaKerja;
                    $tempEmployee->bulan_masa_kerja = $oldBulanMasaKerja;
                    $this->adjustServicePeriodAfterPromotion(
                        $tempEmployee,
                        $oldRank,
                        $newRank
                    );
                    $data['tahun_masa_kerja'] =
                        $tempEmployee->tahun_masa_kerja;
                    $data['bulan_masa_kerja'] =
                        $tempEmployee->bulan_masa_kerja;
                }
                /*
                |--------------------------------------------------------------------------
                | Simpan foto baru
                |--------------------------------------------------------------------------
                */
                $photoUploaded = isset($data['foto'])
                    && $data['foto'] instanceof UploadedFile;
                $oldPhoto = $employee->foto;
                if ($photoUploaded) {
                    $data['foto'] = $data['foto']->store(
                        'pegawai',
                        'public'
                    );
                    if (!$data['foto']) {
                        throw new \RuntimeException(
                            'Foto pegawai gagal disimpan.'
                        );
                    }
                } else {
                    unset($data['foto']);
                }
                /*
                |--------------------------------------------------------------------------
                | Perbarui data pegawai
                |--------------------------------------------------------------------------
                */
                $newNip = $data['nip'] ?? $oldNip;
                $this->employee_repository->update(
                    $oldNip,
                    $data,
                    $user
                );
                // Ambil kembali data pegawai berdasarkan NIP yang tersimpan.
                $employee = $this->employee_repository->getById(
                    $newNip,
                    $user
                );
                // Sinkronkan data akun pengguna dengan data pegawai terbaru.
                $this->syncEmployeeUser($employee, $oldNip);
                /*
                |--------------------------------------------------------------------------
                | Simpan dokumen pegawai
                |--------------------------------------------------------------------------
                */
                $this->saveDocuments(
                    $employee,
                    $documents,
                    $newDocumentPaths,
                    $oldDocumentPaths
                );
                /*
                |--------------------------------------------------------------------------
                | Deteksi perubahan nomor WhatsApp dan email
                |--------------------------------------------------------------------------
                */
                $newPhone = $this->normalizePhone($employee->no_hp);
                $newEmail = $employee->email;
                $phoneChanged = $oldPhone !== $newPhone;
                $emailChanged = $oldEmail !== $newEmail;
                if ($phoneChanged || $emailChanged) {
                    ResendNotificationJob::dispatch($newNip)->afterCommit();
                }
                return [
                    'employee' => $employee,
                    'old_photo' => $oldPhoto,
                    'photo_uploaded' => $photoUploaded,
                ];
            });
        } catch (\Throwable $e) {
            $this->deleteStoredDocuments($newDocumentPaths);
            throw $e;
        }
        /*
        |--------------------------------------------------------------------------
        | Bersihkan file lama setelah transaksi berhasil
        |--------------------------------------------------------------------------
        */
        $this->deleteStoredDocuments($oldDocumentPaths);
        if (
            $result['photo_uploaded'] &&
            $result['old_photo'] &&
            $result['old_photo'] !== self::DEFAULT_PHOTO
        ) {
            $this->deletePhoto($result['old_photo']);
        }
        return $result['employee'];
    }
    public function delete(string $nip): bool
    {
        $user = Auth::user();
        abort_unless($user, 401);
        abort_unless(
            $user->role === 'super_admin',
            403,
            'Hanya Super Admin yang dapat menghapus data pegawai.'
        );
        return DB::transaction(function () use ($nip, $user) {
            $employee = $this->employee_repository->getById($nip, $user);
            $this->authorizeEmployee($employee);
            if ($employee->foto) {
                $this->deletePhoto($employee->foto);
            }
            // Hapus employee
            return $this->employee_repository->delete($nip, $user);
        });
    }
    private function normalizePhone(?string $no_hp): ?string
    {
        if (blank($no_hp)) {
            return null;
        }
        // Hapus semua karakter selain angka
        $no_hp = preg_replace('/\D+/', '', $no_hp);
        // 62812xxxx -> 0812xxxx
        if (str_starts_with($no_hp, '62')) {
            $no_hp = '0' . substr($no_hp, 2);
        }
        // Pastikan selalu diawali 0
        if (!str_starts_with($no_hp, '0')) {
            $no_hp = '0' . $no_hp;
        }
        return $no_hp;
    }
    public function getRanksByEducation(?string $educationLevelCode,?string $statusPegawai = null) {
        return $this->employee_repository->getRanksByEducation($educationLevelCode,$statusPegawai);
    }
    /**
     * Persiapkan data pegawai dan hitung data kepegawaian otomatis.
     */
    private function prepareEmployeeData( array $data, bool $calculateServicePeriod = true ): array {
        /*
        |--------------------------------------------------------------------------
        | Penyesuaian PNS dan PPPK
        |--------------------------------------------------------------------------
        */
        if (($data['status_pegawai'] ?? null) === 'PNS') {
            $pppk = Pppk::where('kode', '0')->first();
            $data['kode_pppk'] = $pppk?->kode;
        }
        if (($data['status_pegawai'] ?? null) === 'PPPK') {
            $rank = Rank::where('kode', '0')->first();
            if ($rank) {
                $data['id_pangkat'] = $rank->id;
            }
            // PPPK tidak menggunakan jadwal kenaikan pangkat PNS.
            $data['tmt_pangkat_lama'] = null;
            $data['tmt_pangkat_baru'] = null;
        }
        /*
        |--------------------------------------------------------------------------
        | Nomor WhatsApp
        |--------------------------------------------------------------------------
        */
        if (!empty($data['no_hp'])) {
            $data['no_hp'] = $this->normalizePhone($data['no_hp']);
        }
        /*
        |--------------------------------------------------------------------------
        | TMT Pangkat Baru
        |--------------------------------------------------------------------------
        */
        if (($data['status_pegawai'] ?? null) === 'PNS') {
            if (!empty($data['tmt_pangkat_lama'])) {
                $data['tmt_pangkat_baru'] = Carbon::parse($data['tmt_pangkat_lama'])->addYears(4)->format('Y-m-d');
            } else {
                $data['tmt_pangkat_baru'] = null;
            }
        }
        /*
        |--------------------------------------------------------------------------
        | TMT KGB Baru
        |--------------------------------------------------------------------------
        */
        if (!empty($data['tmt_kgb_lama'])) {
            $data['tmt_kgb_baru'] = Carbon::parse($data['tmt_kgb_lama'])->addYears(2)->format('Y-m-d');
        } else {
            $data['tmt_kgb_baru'] = null;
        }
        /*
        |--------------------------------------------------------------------------
        | Masa Pensiun Berdasarkan Jabatan
        |--------------------------------------------------------------------------
        */
        $data = $this->calculateRetirementData($data);
        // /*
        // |--------------------------------------------------------------------------
        // | Masa Kerja Berdasarkan Tanggal Masuk
        // |--------------------------------------------------------------------------
        // */
        // if ($calculateServicePeriod) {
        //     $data = $this->calculateServicePeriod($data);
        // }
        return $data;
    }
    /**
     * Hitung masa pensiun berdasarkan BUP pada jabatan.
     */
    private function calculateRetirementData(array $data): array
    {
        /*
        |--------------------------------------------------------------------------
        | Data wajib untuk menghitung pensiun
        |--------------------------------------------------------------------------
        */
        if ( empty($data['tanggal_lahir']) || empty($data['kode_jabatan'])) {
            return $data;
        }
        /*
        |--------------------------------------------------------------------------
        | Ambil jabatan
        |--------------------------------------------------------------------------
        */
        $position = Position::where('kode', $data['kode_jabatan'])->first();
        if (!$position || empty($position->usia_pensiun)) {
            return $data;
        }
        /*
        |--------------------------------------------------------------------------
        | Tanggal lahir
        |--------------------------------------------------------------------------
        */
        $birthDate = Carbon::parse($data['tanggal_lahir']);
        /*
        |--------------------------------------------------------------------------
        | Tanggal mencapai BUP
        |--------------------------------------------------------------------------
        */
        $retirementDate = $birthDate->copy()->addYears((int) $position->usia_pensiun);
        /*
        |--------------------------------------------------------------------------
        | TMT Pensiun
        |--------------------------------------------------------------------------
        | Pensiun efektif pada tanggal 1 bulan berikutnya.
        |--------------------------------------------------------------------------
        */
        $retirementTmt = $retirementDate->copy()->addMonthNoOverflow()->startOfMonth();
        /*
        |--------------------------------------------------------------------------
        | Simpan hasil perhitungan
        |--------------------------------------------------------------------------
        */
        $data['tanggal_pensiun'] = $retirementDate->format('Y-m-d');
        $data['tmt_pensiun'] = $retirementTmt->format('Y-m-d');
        return $data;
    }
    /**
     * Hitung masa kerja berdasarkan tanggal masuk kerja.
     */
    private function calculateServicePeriod(array $data): array
    {
        /*
        |--------------------------------------------------------------------------
        | Jika tanggal masuk kosong
        |--------------------------------------------------------------------------
        */
        if (empty($data['tanggal_masuk'])) {
            $data['tahun_masa_kerja'] = 0;
            $data['bulan_masa_kerja'] = 0;
            return $data;
        }
        $startDate = Carbon::parse($data['tanggal_masuk']);
        $today = Carbon::today();
        /*
        |--------------------------------------------------------------------------
        | Jika tanggal masuk kerja masih di masa depan
        |--------------------------------------------------------------------------
        */
        if ($startDate->gt($today)) {
            $data['tahun_masa_kerja'] = 0;
            $data['bulan_masa_kerja'] = 0;
            return $data;
        }
        /*
        |--------------------------------------------------------------------------
        | Hitung selisih tahun dan bulan
        |--------------------------------------------------------------------------
        */
        $diff = $startDate->diff($today);
        $data['tahun_masa_kerja'] = $diff->y;
        $data['bulan_masa_kerja'] = $diff->m;
        return $data;
    }
    /**
     * Upload photo
     */
    private function handlePhoto(array $data, ?string $nip = null): array
    {
        if (isset($data['foto']) &&$data['foto'] instanceof UploadedFile) {
            if ($nip) {
                $this->deleteOldPhoto($nip);
            }
            $data['foto'] = $data['foto']->store('pegawai', 'public');
        } else {
            $data['foto'] = $nip ? ($this->getOldPhoto($nip) ?: self::DEFAULT_PHOTO) : self::DEFAULT_PHOTO;
        }
        return $data;
    }
    private function getOldPhoto(string $nip): ?string
    {
        $user = Auth::user();
        abort_unless($user, 401);
        return $this->employee_repository->getById($nip, $user, ['foto'])?->foto;
    }
    private function deleteOldPhoto(string $nip): void
    {
        $foto = $this->getOldPhoto($nip);
        if ($foto && $foto !== self::DEFAULT_PHOTO) {
            $this->deletePhoto($foto);
        }
    }
    private function deletePhoto(string $foto): void
    {
        if ($foto !== self::DEFAULT_PHOTO && Storage::disk('public')->exists($foto)) {
            Storage::disk('public')->delete($foto);
        }
    }
    /**
     * Simpan dokumen pegawai ke penyimpanan privat.
     *
     * Dokumen lama tidak langsung dihapus.
     * Penghapusan dilakukan setelah transaksi database berhasil.
     */
    private function saveDocuments(
        Employee $employee,
        array $documents,
        array &$newDocumentPaths,
        array &$oldDocumentPaths
    ): void {
        $user = Auth::user();
        $jenisDokumenDiizinkan = [
            'SK_KGB',
            'SK_PANGKAT',
            'SK_JABATAN',
            'IJAZAH_TERAKHIR',
            'SK_PENGANGKATAN_PNS',
            'SK_PENGANGKATAN_PPPK',
            'KTP',
        ];
        foreach ($documents as $jenis => $file) {
            // File yang tidak dipilih tidak mengubah dokumen lama.
            if ($file === null) {
                continue;
            }
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                throw new \RuntimeException(
                    'File dokumen ' . $jenis . ' tidak valid.'
                );
            }
            if (!in_array($jenis, $jenisDokumenDiizinkan, true)) {
                throw new \InvalidArgumentException(
                    'Jenis dokumen tidak diizinkan: ' . $jenis
                );
            }
            /*
             * Simpan file baru di disk privat.
             */
            $pathBaru = $file->store(
                'employee-documents/' . $employee->nip,
                'local'
            );
            if (!$pathBaru) {
                throw new \RuntimeException(
                    'Dokumen ' . $jenis . ' gagal disimpan.'
                );
            }
            // Catat segera agar dapat dibersihkan jika transaksi gagal.
            $newDocumentPaths[] = $pathBaru;
            /*
             * Cari dokumen lama untuk kategori yang sama.
             */
            $dokumenLama = EmployeeDocument::query()
                ->where('nip', $employee->nip)
                ->where('jenis_dokumen', $jenis)
                ->first();
            if ($dokumenLama) {
                $pathLama = $dokumenLama->nama_file;
                $dokumenLama->update([
                    'id_user' => $user?->id,
                    'nama_file' => $pathBaru,
                    'nama_file_asli' => $file->getClientOriginalName(),
                ]);
                if ($pathLama && $pathLama !== $pathBaru) {
                    $oldDocumentPaths[] = $pathLama;
                }
            } else {
                EmployeeDocument::create([
                    'nip' => $employee->nip,
                    'id_user' => $user?->id,
                    'jenis_dokumen' => $jenis,
                    'nama_file' => $pathBaru,
                    'nama_file_asli' => $file->getClientOriginalName(),
                ]);
            }
        }
    }
    /**
     * Hapus file dari disk privat.
     */
    private function deleteStoredDocuments(array $paths): void
    {
        $storage = Storage::disk('local');
        foreach (array_unique($paths) as $path) {
            if (
                is_string($path)
                && $path !== ''
                && !str_contains($path, '..')
                && $storage->exists($path)
            ) {
                $storage->delete($path);
            }
        }
    }
    public function getMasterData(User $user)
    {
        return $this->employee_repository->getMasterData($user);
    }
    public function export(string $format, array $columns)
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        // Hanya Super Admin dan Admin yang boleh melakukan export.
        $this->authorizeEmployeeManagement();
        $format = strtolower($format);
        abort_unless(
            in_array($format, ['xlsx', 'csv', 'pdf'], true),
            404,
            'Format ekspor tidak didukung.'
        );
        $availableColumns = EmployeeExport::availableColumns();
        $columns = array_values(array_unique($columns));
        abort_if(
            empty($columns),
            422,
            'Pilih minimal satu kolom.'
        );
        foreach ($columns as $column) {
            abort_unless(
                is_string($column)
                    && array_key_exists($column, $availableColumns),
                422,
                'Pilihan kolom tidak valid.'
            );
        }
        if ($format === 'pdf') {
            abort_if(
                count($columns) > 9,
                422,
                'Ekspor PDF maksimal 9 kolom.'
            );
        }
        // Data sudah dibatasi berdasarkan kewenangan pengguna.
        $employees = $this->employee_repository->getExportData($user);
        $filename = 'pegawai-' . now('Asia/Jakarta')->format('Ymd-His');
        $export = new EmployeeExport($employees, $columns);
        if ($format === 'pdf') {
            $headings = $export->headings();
            $groups = $employees
                ->filter(fn ($employee) => $employee->department !== null)
                ->groupBy(fn ($employee) => $employee->department->nama)
                ->map(function ($unitEmployees, $unitName) use ($export) {
                    return [
                        'unit_kerja' => $unitName,
                        'rows' => $unitEmployees
                            ->values()
                            ->map(fn ($employee) => $export->map($employee))
                            ->all(),
                    ];
                })
                ->sortKeys()
                ->values();
            return Pdf::loadView('employee.export-pdf', [
                'headings' => $headings,
                'groups' => $groups,
            ])
                ->setPaper('a4', 'landscape')
                ->download($filename . '.pdf');
        }
        return match ($format) {
            'xlsx' => Excel::download(
                $export,
                $filename . '.xlsx',
                ExcelFormat::XLSX
            ),
            'csv' => Excel::download(
                $export,
                $filename . '.csv',
                ExcelFormat::CSV
            ),
        };
    }
    //
    public function import(UploadedFile $file): void
    {
        $user = $this->authorizeEmployeeManagement();
        $extension = strtolower($file->getClientOriginalExtension());
        abort_unless(
            in_array($extension, ['xlsx', 'xls', 'csv'], true),
            422,
            'Format file tidak didukung.'
        );
        DB::transaction(function () use ($file, $user) {
            $this->employeeImport->setUser($user);
            Excel::import($this->employeeImport, $file);
        });
    }
    public function downloadTemplate(string $format)
    {
        $this->authorizeEmployeeManagement();
        return match ($format) {
            'xlsx' => Excel::download(new EmployeeTemplateExport,'template_pegawai.xlsx'),
            'csv' => Excel::download(new EmployeeTemplateExport,'template_pegawai.csv'),
            default => abort(404),
        };
    }
    public function syncTmt(): void
    {
        $today = Carbon::today();
        Employee::with(['rank', 'educationLevel'])
            ->where(function ($query) use ($today) {
                $query->where(function ($query) use ($today) {
                    $query->where('status_pegawai', 'PPPK')
                        ->whereDate('tmt_kgb_baru', '<=', $today);
                })->orWhereDate('tmt_pangkat_baru', '<=', $today)->orWhereDate('tmt_pensiun', '<=', $today);
            })
            ->chunkById(
                100,
                function ($employees) use ($today) {
                    foreach ($employees as $employee) {
                        $updated = false;
                        /*
                        |--------------------------------------------------------------------------
                        | KGB PPPK
                        |--------------------------------------------------------------------------
                        | TMT KGB berubah setiap 2 tahun.
                        | Masa kerja bertambah 2 tahun untuk setiap periode KGB.
                        | KGB PNS diproses melalui printKgb().
                        |--------------------------------------------------------------------------
                        */
                        if ( $employee->status_pegawai === 'PPPK' && $employee->tmt_kgb_baru
                        ) {
                            $tmtKgb = Carbon::parse($employee->tmt_kgb_baru);
                            while ($tmtKgb->lte($today)) {
                                $employee->tmt_kgb_lama = $tmtKgb->copy();
                                $tmtKgb->addYearsNoOverflow(2);
                                $employee->tahun_masa_kerja = (int) $employee->tahun_masa_kerja + 2;
                                $updated = true;
                            }
                            $employee->tmt_kgb_baru = $tmtKgb;
                        }
                        /*
                        |--------------------------------------------------------------------------
                        | Kenaikan Pangkat PNS
                        |--------------------------------------------------------------------------
                        | PPPK tidak diproses untuk kenaikan pangkat otomatis.
                        |--------------------------------------------------------------------------
                        */
                        if ( $employee->status_pegawai === 'PNS' && $employee->tmt_pangkat_baru ) {
                            $educationLevel = $employee->educationLevel;
                            $rank = $employee->rank;
                            if (!$rank || !$educationLevel) {
                                $employee->tmt_pangkat_baru = null;
                                $updated = true;
                            } elseif ( $educationLevel->pangkat_pns_maksimum !== null && $rank->urutan_pangkat >= $educationLevel->pangkat_pns_maksimum ) {
                                $employee->tmt_pangkat_baru = null;
                                $updated = true;
                            } elseif ( $educationLevel->pangkat_pns_maksimum === null ) {
                                $employee->tmt_pangkat_baru = null;
                                $updated = true;
                            } else {
                                $tmtPangkat = Carbon::parse( $employee->tmt_pangkat_baru );
                                while ($tmtPangkat->lte($today)) {
                                    $employee->tmt_pangkat_lama = $tmtPangkat->copy();
                                    $rankLama = $employee->rank;
                                    $kodePangkatBerikutnya = $this->nextRank[$rankLama->kode] ?? null;
                                    if (!$kodePangkatBerikutnya) {
                                        $employee->tmt_pangkat_baru = null;
                                        $updated = true;
                                        break;
                                    }
                                    $rankBaru = Rank::where( 'kode', $kodePangkatBerikutnya )->first();
                                    if (!$rankBaru) {
                                        $employee->tmt_pangkat_baru = null;
                                        $updated = true;
                                        break;
                                    }
                                    /*
                                    |--------------------------------------------------------------------------
                                    | Validasi batas pangkat berdasarkan pendidikan
                                    |--------------------------------------------------------------------------
                                    */
                                    if ( $rankBaru->urutan_pangkat > $educationLevel->pangkat_pns_maksimum ) {
                                        $employee->tmt_pangkat_baru = null;
                                        $updated = true;
                                        break;
                                    }
                                    /*
                                    |--------------------------------------------------------------------------
                                    | Cek riwayat kenaikan pangkat
                                    |--------------------------------------------------------------------------
                                    */
                                    $exists = PromotionHistory::where( 'nip', $employee->nip )
                                        ->whereDate( 'tmt_pangkat', $employee->tmt_pangkat_lama )->exists();
                                    if (!$exists) {
                                        PromotionHistory::create([
                                            'nip' => $employee->nip,
                                            'id_pangkat_lama' => $rankLama->id,
                                            'id_pangkat_baru' => $rankBaru->id,
                                            'tmt_pangkat' => $employee->tmt_pangkat_lama,
                                            'keterangan' => 'Kenaikan pangkat otomatis',
                                        ]);
                                    }
                                    /*
                                    |--------------------------------------------------------------------------
                                    | Penyesuaian masa kerja
                                    |--------------------------------------------------------------------------
                                    */
                                    $this->adjustServicePeriodAfterPromotion( $employee, $rankLama, $rankBaru);
                                    $employee->id_pangkat = $rankBaru->id;
                                    $employee->setRelation('rank', $rankBaru);
                                    /*
                                    |--------------------------------------------------------------------------
                                    | Hentikan jika mencapai pangkat maksimum
                                    |--------------------------------------------------------------------------
                                    */
                                    if ( $rankBaru->urutan_pangkat >= $educationLevel->pangkat_pns_maksimum ) {
                                        $employee->tmt_pangkat_baru = null;
                                        $updated = true;
                                        break;
                                    }
                                    /*
                                    |--------------------------------------------------------------------------
                                    | Jadwal kenaikan pangkat berikutnya
                                    |--------------------------------------------------------------------------
                                    */
                                    $tmtPangkat->addYearsNoOverflow(4);
                                    $updated = true;
                                }
                                if ($employee->tmt_pangkat_baru !== null) {
                                    $employee->tmt_pangkat_baru = $tmtPangkat;
                                }
                            }
                        }
                        /*
                        |--------------------------------------------------------------------------
                        | Pensiun
                        |--------------------------------------------------------------------------
                        */
                        if ( $employee->tmt_pensiun && Carbon::parse($employee->tmt_pensiun)->lte($today) && $employee->status !== 'Pensiun'     ) {
                            $employee->status = 'Pensiun';
                            $updated = true;
                        }
                        /*
                        |--------------------------------------------------------------------------
                        | Simpan perubahan
                        |--------------------------------------------------------------------------
                        */
                        if ($updated) {
                            $employee->save();
                        }
                    }
                },
                'nip',
                'nip'
            );
    }
    /**
     * Penyesuaian masa kerja setelah kenaikan golongan.
     *
     * Aturan:
     * Golongan I  → II : dikurangi 6 tahun
     * Golongan II → III: dikurangi 5 tahun
     * Golongan III → IV: dikurangi 1 bulan
     */
    private function adjustServicePeriodAfterPromotion(
        Employee $employee,
        Rank $oldRank,
        Rank $newRank
    ): void {
        $groupMap = [
            'I'   => 1,
            'II'  => 2,
            'III' => 3,
            'IV'  => 4,
        ];
        $oldGroupCode = strtoupper(
            explode('/', $oldRank->kode)[0]
        );
        $newGroupCode = strtoupper(
            explode('/', $newRank->kode)[0]
        );
        $oldGroup = $groupMap[$oldGroupCode] ?? 0;
        $newGroup = $groupMap[$newGroupCode] ?? 0;
        /*
        |--------------------------------------------------------------------------
        | Hitung masa kerja dalam total bulan
        |--------------------------------------------------------------------------
        */
        $totalMonths =
            ((int) $employee->tahun_masa_kerja * 12)
            + (int) $employee->bulan_masa_kerja;
        /*
        |--------------------------------------------------------------------------
        | Golongan I → II
        | Kurangi 6 tahun = 72 bulan
        |--------------------------------------------------------------------------
        */
        if ($oldGroup === 1 && $newGroup === 2) {
            $totalMonths = max(0, $totalMonths - 72);
        }
        /*
        |--------------------------------------------------------------------------
        | Golongan II → III
        | Kurangi 5 tahun = 60 bulan
        |--------------------------------------------------------------------------
        */
        elseif ($oldGroup === 2 && $newGroup === 3) {
            $totalMonths = max(0, $totalMonths - 60);
        }
        /*
        |--------------------------------------------------------------------------
        | Golongan III → IV
        | Kurangi 1 bulan
        |--------------------------------------------------------------------------
        */
        elseif ($oldGroup === 3 && $newGroup === 4) {
            $totalMonths = max(0, $totalMonths - 1);
        }
        /*
        |--------------------------------------------------------------------------
        | Kembalikan total bulan ke tahun dan bulan
        |--------------------------------------------------------------------------
        */
        $employee->tahun_masa_kerja = intdiv(
            $totalMonths,
            12
        );
        $employee->bulan_masa_kerja = $totalMonths % 12;
    }
    public function authorizeEmployee(Employee $employee): void
    {
        $user = Auth::user();
        abort_unless($user, 401);
        if ($user->role === 'super_admin') {
            return;
        }
        if ($user->role === 'admin') {
            abort_unless(
                filled($user->kode_unit_kerja) &&
                (string) $user->kode_unit_kerja === (string) $employee->kode_unit_kerja,
                403,
                'Anda hanya dapat mengelola data pegawai pada unit kerja sendiri.'
            );
            return;
        }
        if ($user->role === 'user') {
            abort_unless(
                filled($user->nip) &&
                (string) $user->nip === (string) $employee->nip,
                403,
                'Anda hanya dapat mengakses data pegawai milik sendiri.'
            );
            return;
        }
        abort(403, 'Anda tidak memiliki hak akses untuk mengakses data pegawai ini.');
    }
    /**
     * Membuat atau menyinkronkan akun User berdasarkan data pegawai.
     */
    protected function syncEmployeeUser(Employee $employee,  ?string $oldNip = null): User
    {
        return $this->employeeUserSyncService->sync($employee, $oldNip);
    }
    /**
     * Hanya Super Admin dan Admin yang dapat mengelola data pegawai.
     */
    private function authorizeEmployeeManagement(): User
    {
        $user = Auth::user();
        abort_unless($user, 401);
        abort_unless(
            in_array($user->role, ['super_admin', 'admin'], true),
            403,
            'Anda tidak memiliki hak akses untuk mengelola data pegawai.'
        );
        if ($user->role === 'admin') {
            abort_unless(
                filled($user->kode_unit_kerja),
                403,
                'Unit kerja Admin belum ditentukan.'
            );
        }
        return $user;
    }
    protected function syncImportedEmployeeUser(Employee $employee): User
    {
        return $this->employeeUserSyncService->sync($employee);
    }
}

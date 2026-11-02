<?php
namespace App\Imports;
use App\Models\City;
use App\Models\Department;
use App\Models\District;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Province;
use App\Models\Pppk;
use App\Models\Rank;
use App\Models\UrbanVillage;
use App\Models\User;
use App\Repositories\EmployeeRepository;
use App\Services\EmployeeService;
use App\Services\EmployeeUserSyncService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
class EmployeeImport implements ToCollection, WithHeadingRow
{
    private array $usedNik = [];
    private array $usedEmail = [];
    private array $usedPhone = [];
    private array $usedNip = [];
    public function __construct(
        private EmployeeRepository $employeeRepository,
        private EmployeeUserSyncService $employeeUserSyncService
    ) {
    }
    private ?User $user = null;
    public function setUser(User $user): void
    {
        $this->user = $user;
    }
    /*
    |--------------------------------------------------------------------------
    | COLLECTION
    |--------------------------------------------------------------------------
    */
    public function collection(Collection $rows): void
    {
        $user = $this->user;
        abort_unless($user instanceof User, 401, 'Pengguna belum terautentikasi.');
        abort_unless(
            in_array($user->role, ['admin', 'super_admin'], true),
            403,
            'Anda tidak memiliki izin untuk mengimpor data pegawai.'
        );
        foreach ($rows as $row) {
            /*
            |--------------------------------------------------------------------------
            | LEWATI BARIS KOSONG
            |--------------------------------------------------------------------------
            */
            if ($row->filter()->isEmpty()) {
                continue;
            }
            /*
            |--------------------------------------------------------------------------
            | VALIDASI NIP
            |--------------------------------------------------------------------------
            | NIP wajib terdiri dari tepat 18 digit angka.
            | Jika tidak sesuai, baris pegawai tidak disimpan.
            */
            $nip = $this->string($row['nip'] ?? null);
            if (
                $nip === null ||
                !preg_match('/\A[0-9]{18}\z/', $nip)
            ) {
                // Log::warning('Import pegawai dilewati karena NIP tidak valid.', [
                //     'nip' => $nip,
                // ]);
                continue;
            }
            /*
            |--------------------------------------------------------------------------
            | VALIDASI UNIT KERJA IMPORT
            |--------------------------------------------------------------------------
            | Unit kerja kosong diperbolehkan.
            | Jika terisi, nama unit harus ditemukan di database.
            | Admin hanya boleh mengimpor unit kerjanya sendiri.
            |--------------------------------------------------------------------------
            */
            $unitKerja = $this->string($row['unit_kerja'] ?? null);
            $departmentCode = null;
            if ($unitKerja !== null) {
                $departmentCode = $this->departmentCode($unitKerja);
                // Unit kerja terisi tetapi tidak ditemukan di database.
                if ($departmentCode === null) {
                    // Log::warning('Import pegawai dilewati karena unit kerja tidak ditemukan.', [
                    //     'nip' => $nip,
                    //     'unit_kerja' => $unitKerja,
                    //     'username' => $user->username,
                    // ]);
                    continue;
                }
                // Admin hanya boleh mengimpor unit kerjanya sendiri.
                if ( $user->role === 'admin' && ( blank($user->kode_unit_kerja) || (string) $departmentCode !== (string) $user->kode_unit_kerjam)) {
                    // Log::warning('Import pegawai dilewati karena unit kerja tidak sesuai dengan pengguna.', [
                    //     'nip' => $nip,
                    //     'unit_kerja_import' => $unitKerja,
                    //     'kode_unit_kerja_import' => $departmentCode,
                    //     'kode_unit_kerja_user' => $user->kode_unit_kerja,
                    //     'username' => $user->username,
                    // ]);
                    continue;
                }
            }
            /*
            |--------------------------------------------------------------------------
            | CEK NIP DUPLIKAT DALAM FILE
            |--------------------------------------------------------------------------
            */
            if (isset($this->usedNip[$nip])) {
                continue;
            }
            /*
            |--------------------------------------------------------------------------
            | CEK NIP DI DATABASE
            |--------------------------------------------------------------------------
            */
            if (Employee::query()->where('nip', $nip)->exists()) {
                continue;
            }
            $this->usedNip[$nip] = true;
            /*
            |--------------------------------------------------------------------------
            | STATUS PEGAWAI
            |--------------------------------------------------------------------------
            */
            $statusPegawai =
                $this->normalizeStatusPegawai($row['status_pegawai'] ?? null);
            /*
            |--------------------------------------------------------------------------
            | DATA IMPORT
            |--------------------------------------------------------------------------
            */
            $this->storeEmployee([
                /*
                |--------------------------------------------------------------------------
                | DATA PEGAWAI
                |--------------------------------------------------------------------------
                */
                'nama' => $this->title($row['nama'] ?? null),
                'nip' => $nip,
                'nik' => $this->string($row['nik'] ?? null),
                'email' => $this->lower($row['email'] ?? null),
                'no_hp' => $this->normalizePhone($this->string($row['no_hp'] ?? null)),
                'status_pegawai' => $statusPegawai,
                // Pangkat/Golongan
                'pangkat_pns' => $this->string($row['pangkat_pns'] ?? null),
                'golongan_pppk' => $this->string($row['golongan_pppk'] ?? null),
                /*
                |--------------------------------------------------------------------------
                | KEPEGAWAIAN
                |--------------------------------------------------------------------------
                */
                'jabatan' => $this->string($row['jabatan'] ?? null),
                'unit_kerja' => $this->string($row['unit_kerja'] ?? null),
                'pendidikan' => $this->string($row['pendidikan'] ?? null),
                /*
                |--------------------------------------------------------------------------
                | DATA PRIBADI
                |--------------------------------------------------------------------------
                */
                'tempat_lahir' => $this->string($row['tempat_lahir'] ?? null),
                'tanggal_lahir' => $this->date($row['tanggal_lahir'] ?? null),
                'jenis_kelamin' =>$this->normalizeJenisKelamin($row['jenis_kelamin'] ?? null),
                'agama' => $this->normalizeAgama($row['agama'] ?? null),
                'status_nikah' => $this->normalizeStatusNikah($row['status_pernikahan'] ?? null),
                'alamat' => $this->string( $row['alamat'] ?? null),
                /*
                |--------------------------------------------------------------------------
                | WILAYAH
                |--------------------------------------------------------------------------
                |
                | WithHeadingRow:
                |
                | Kota/Kabupaten  -> kota_kabupaten
                | Kelurahan/Desa  -> kelurahan_desa
                |
                */
                // 'provinsi' => $this->string($row['provinsi'] ?? null),
                // 'kota' => $this->string($row['kotakabupaten'] ?? null),
                // 'kecamatan' => $this->string($row['kecamatan'] ?? null),
                // 'kelurahan' => $this->string($row['kelurahandesa'] ?? null),
                'provinsi' => $this->normalizeProvinceName($this->string($row['provinsi'] ?? null)),
                'kota' => $this->normalizeCityName($this->string($row['kotakabupaten'] ?? null)),
                'kecamatan' => $this->normalizeDistrictName($this->string($row['kecamatan'] ?? null)),
                'kelurahan' => $this->normalizeUrbanVillageName($this->string($row['kelurahandesa'] ?? null)),
                /*
                |--------------------------------------------------------------------------
                | TANGGAL MASUK
                |--------------------------------------------------------------------------
                */
                'tanggal_masuk' => $this->date($row['tanggal_masuk'] ?? null),
                'masa_kerja' => $this->string($row['masa_kerja'] ?? null),
                // TMT
                'tmt_pangkat_lama' => $this->date($row['tmt_pangkat_lama'] ?? null),
                'tmt_kgb_lama' => $this->date($row['tmt_kgb_lama'] ?? null),
                'tanggal_pensiun' => $this->date($row['tanggal_pensiun'] ?? null),
                /*
                |--------------------------------------------------------------------------
                | STATUS
                |--------------------------------------------------------------------------
                */
                'status' => $this->normalizeStatus($row['status'] ?? null),
            ]);
        }
    }
    /*
    |--------------------------------------------------------------------------
    | STORE EMPLOYEE
    |--------------------------------------------------------------------------
    */
    private function storeEmployee(array $row): void
    {
        /*
        |--------------------------------------------------------------------------
        | UNIQUE
        |--------------------------------------------------------------------------
        */
        $nik = $this->uniqueValue( $row['nik'] ?? null, 'nik');
        $email = $this->uniqueValue( $row['email'] ?? null, 'email');
        $rawPhone = $this->string( $row['no_hp'] ?? null);
        $normalizedPhone = $this->normalizePhone($rawPhone );
        $phone = $this->uniqueValue( $normalizedPhone, 'no_hp');
        /*
        |--------------------------------------------------------------------------
        | STATUS PEGAWAI
        |--------------------------------------------------------------------------
        */
        $statusPegawai = $row['status_pegawai'];
        /*
        |--------------------------------------------------------------------------
        | PANGKAT / GOLONGAN
        |--------------------------------------------------------------------------
        */
        $rankId = null;
        $kodePppk = null;
        if ($statusPegawai === 'PNS') {
            /*
            | PNS:
            | Pangkat PNS dari Excel.
            */
            $rankId = $this->rankId( $row['pangkat_pns'] ?? null);
            /*
            | PNS:
            | PPPK menggunakan fallback 0.
            */
            $kodePppk = $this->pppkCodeZero();
        }
        elseif ($statusPegawai === 'PPPK') {
            /*
            | PPPK:
            | Pangkat PNS menggunakan fallback 0.
            */
            $rankId = $this->rankIdZero();
            /*
            | PPPK:
            | Golongan PPPK dari Excel.
            */
            $kodePppk = $this->pppkCode($row['golongan_pppk'] ?? null );
        }
        /*
        |--------------------------------------------------------------------------
        | UNIT KERJA
        |--------------------------------------------------------------------------
        */
        $departmentCode = $this->departmentCode( $row['unit_kerja'] ?? null);
        /*
        |--------------------------------------------------------------------------
        | JABATAN
        |--------------------------------------------------------------------------
        */
        $position = $this->findPosition( $row['jabatan'] ?? null);
        $positionCode = $position?->kode;
        /*
        |--------------------------------------------------------------------------
        | PENDIDIKAN
        |--------------------------------------------------------------------------
        */
        $educationLevelCode = $this->educationLevelCode( $row['pendidikan'] ?? null );
        /*
        |--------------------------------------------------------------------------
        | TEMPAT LAHIR
        |--------------------------------------------------------------------------
        */
        $birthPlaceId = $this->cityId( $row['tempat_lahir'] ?? null );
        /*
        |--------------------------------------------------------------------------
        | WILAYAH
        |--------------------------------------------------------------------------
        */
        $provinceId = $this->provinceId( $row['provinsi'] ?? null );
        $cityId = $this->cityId( $row['kota'] ?? null, $provinceId );
        $districtId = $this->districtId( $row['kecamatan'] ?? null, $cityId );
        $urbanVillageId = $this->urbanVillageId( $row['kelurahan'] ?? null, $districtId );
        /*
        |--------------------------------------------------------------------------
        | TANGGAL MASUK
        |--------------------------------------------------------------------------
        */
        $employmentStartDate = $row['tanggal_masuk'] ?? null;
        /*
        |--------------------------------------------------------------------------
        | MASA KERJA
        |--------------------------------------------------------------------------
        */
        $masaKerja = $this->parseMasaKerja( $row['masa_kerja'] ?? null );
        /*
        |--------------------------------------------------------------------------
        | TMT PANGKAT
        |--------------------------------------------------------------------------
        */
        $previousRankTmt = null;
        $newRankTmt = null;
        if ($statusPegawai === 'PNS') {
            $previousRankTmt = $row['tmt_pangkat_lama'] ?? null;
            $newRankTmt = $this->calculateTmtPangkatBaru( $statusPegawai, $previousRankTmt );
        }
        /*
        |--------------------------------------------------------------------------
        | TMT KGB
        |--------------------------------------------------------------------------
        */
        $previousKgbTmt = $row['tmt_kgb_lama'] ?? null;
        $newKgbTmt = $this->addYears( $previousKgbTmt, 2 );
        /*
        |--------------------------------------------------------------------------
        | PENSIUN
        |--------------------------------------------------------------------------
        */
        $retirementDate = $this->calculateRetirementDate( $row['tanggal_lahir'] ?? null, $position );
        /*
        | Jika tidak dapat dihitung dari jabatan,
        | gunakan nilai Excel.
        */
        if (!$retirementDate) {
            $retirementDate = $row['tanggal_pensiun'] ?? null;
        }
        /*
        |--------------------------------------------------------------------------
        | TMT PENSIUN
        |--------------------------------------------------------------------------
        */
        $retirementTmt = $this->calculateRetirementTmt( $retirementDate );
        /*
        |--------------------------------------------------------------------------
        | DATA SESUAI MIGRATION EMPLOYEES
        |--------------------------------------------------------------------------
        */
        $data = [
            /*
            | Primary Key
            */
            'nip' => $row['nip'],
            /*
            | Data Pegawai
            */
            'nama' => $row['nama'],
            'nik' => $nik,
            'email' => $email,
            'no_hp' => $phone,
            /*
            | Pangkat / Golongan
            */
            'id_pangkat' => $rankId,
            'kode_pppk' => $kodePppk,
            /*
            | Unit Kerja / Jabatan
            */
            'kode_unit_kerja' => $departmentCode,
            'kode_jabatan' => $positionCode,
            /*
            | Pendidikan
            */
            'tingkat_pendidikan' => $educationLevelCode,
            /*
            | Kelahiran
            */
            'tempat_lahir' => $birthPlaceId,
            'tanggal_lahir' => $row['tanggal_lahir'],
            /*
            | Data Pribadi
            */
            'jenis_kelamin' => $row['jenis_kelamin'],
            'agama' => $row['agama'],
            'alamat' => $row['alamat'],
            /*
            | Wilayah
            */
            'id_provinsi' => $provinceId,
            'id_kota' => $cityId,
            'id_kecamatan' => $districtId,
            'id_kelurahan' => $urbanVillageId,
            /*
            | Status
            */
            'status_pegawai' => $statusPegawai,
            'status_nikah' => $row['status_nikah'],
            'status' => $row['status'] ?? 'Aktif',
            /*
            | Tanggal Masuk
            */
            'tanggal_masuk' => $employmentStartDate,
            /*
            | TMT Pangkat
            */
            'tmt_pangkat_lama' => $previousRankTmt,
            'tmt_pangkat_baru' => $newRankTmt,
            /*
            | TMT KGB
            */
            'tmt_kgb_lama' => $previousKgbTmt,
            'tmt_kgb_baru' => $newKgbTmt,
            /*
            | Pensiun
            */
            'tanggal_pensiun' => $retirementDate,
            'tmt_pensiun' => $retirementTmt,
            /*
            | Masa Kerja
            */
            'tahun_masa_kerja' => $masaKerja['tahun'],
            'bulan_masa_kerja' => $masaKerja['bulan'],
            /*
            | Foto
            */
            'foto' => 'images/default-user.png',
        ];
        /*
        |--------------------------------------------------------------------------
        | SIMPAN
        |--------------------------------------------------------------------------
        */
        $employee = $this->employeeRepository->updateOrCreate(
            [
                'nip' => $row['nip'],
            ],
            $data,
            $this->user
        );

        // Sinkronisasi akun pengguna berdasarkan NIP.
        $this->employeeUserSyncService->sync($employee);
    }
    /*
    |--------------------------------------------------------------------------
    | PARSE MASA KERJA DARI EXCEL
    |--------------------------------------------------------------------------
    */
    private function parseMasaKerja($value): array
    {
        $default = [
            'tahun' => 0,
            'bulan' => 0,
        ];
        if (blank($value)) {
            return $default;
        }
        $value = trim((string) $value);
        if (
            preg_match('/([0-9]+)\s*tahun/i', $value, $tahunMatch) &&
            preg_match('/([0-9]+)\s*bulan/i', $value, $bulanMatch)
        ) {
            $tahun = (int) $tahunMatch[1];
            $bulan = (int) $bulanMatch[1];
            // Bulan harus berada dalam rentang 0–11.
            if ($bulan < 0 || $bulan > 11) {
                // Log::warning('Masa kerja Excel tidak valid.', [
                //     'masa_kerja' => $value,
                // ]);
                return $default;
            }
            return [
                'tahun' => $tahun,
                'bulan' => $bulan,
            ];
        }
        // Log::warning('Format masa kerja Excel tidak valid.', [
        //     'masa_kerja' => $value,
        // ]);
        return $default;
    }
    /*
    |--------------------------------------------------------------------------
    | RANK PNS
    |--------------------------------------------------------------------------
    */
    private function rankId( ?string $value ): ?int {
        if (blank($value)) {
            return null;
        }
        $value = $this->cleanReferenceName($value);
        if (blank($value)) {
            return null;
        }
        $value = $this->normalizeReference($value);
        return Rank::query()->where( 'urutan_pangkat', '>', 0)->get([ 'id', 'kode', 'nama', 'urutan_pangkat',])
            ->first(function ($rank) use ($value) {
                return $this->normalizeReference( $rank->nama) === $value || $this->normalizeReference( $rank->kode) === $value;
            })?->id;
    }
    /*
    |--------------------------------------------------------------------------
    | RANK PNS DEFAULT
    |--------------------------------------------------------------------------
    */
    private function rankIdZero(): ?int
    {
        return Rank::query()->where( 'kode', '0')->where( 'urutan_pangkat', 0)->value('id');
    }
    /*
    |--------------------------------------------------------------------------
    | PPPK
    |--------------------------------------------------------------------------
    */
    private function pppkCode(?string $value): ?string {
        if (blank($value)) {
            return null;
        }
        $value = $this->cleanReferenceName($value);
        if (blank($value)) { return null;
        }
        $value = $this->normalizeReference($value);
        return Pppk::query() ->where( 'urutan_golongan', '>', 0)->get([ 'kode', 'nama', 'urutan_golongan',])
            ->first(function ($pppk) use ($value) {
                return $this->normalizeReference( $pppk->nama ) === $value || $this->normalizeReference( $pppk->kode ) === $value;
            })?->kode;
    }
    /*
    |--------------------------------------------------------------------------
    | PPPK DEFAULT
    |--------------------------------------------------------------------------
    */
    private function pppkCodeZero(): ?string
    {
        return Pppk::query()->where( 'kode', '0')->where( 'urutan_golongan', 0)->value('kode');
    }
    /*
    |--------------------------------------------------------------------------
    | CLEAN REFERENCE NAME
    |--------------------------------------------------------------------------
    */
    private function cleanReferenceName(?string $value): ?string {
        if (blank($value)) {
            return null;
        }
        $value = trim((string) $value);
        return trim(preg_replace( '/\s*\([^)]*\)\s*$/', '', $value));
    }
    /*
    |--------------------------------------------------------------------------
    | UNIT KERJA
    |--------------------------------------------------------------------------
    */
    private function departmentCode(?string $value): ?string {
        if (blank($value)) {
            return null;
        }
        $searchName = $this->normalizeReference($value);
        return Department::query()->get([ 'kode', 'nama'])
            ->first(function ($department) use ($searchName) {
                return $this->normalizeReference( $department->nama) === $searchName;
            })?->kode;
    }
    /*
    |--------------------------------------------------------------------------
    | JABATAN
    |--------------------------------------------------------------------------
    */
    private function findPosition(?string $name): ?Position {
        if (blank($name)) {
            return null;
        }
        $searchName = $this->normalizeReference($name);
        return Position::query()->get()
            ->first(function ($position) use ($searchName) {
                return $this->normalizeReference( $position->nama ) === $searchName;
            });
    }
    /*
    |--------------------------------------------------------------------------
    | PENDIDIKAN
    |--------------------------------------------------------------------------
    */
    private function educationLevelCode(?string $value): ?string {
        if (blank($value)) {
            return null;
        }
        $searchName = $this->normalizeReference($value);
        return EducationLevel::query()
            ->get([ 'kode', 'nama' ])
            ->first(function ($education) use ($searchName) {
                return $this->normalizeReference( $education->nama ) === $searchName || $this->normalizeReference( $education->kode ) === $searchName;
            })?->kode;
    }
    /*
    |--------------------------------------------------------------------------
    | PROVINCE
    |--------------------------------------------------------------------------
    */
    private function provinceId(
        ?string $value
    ): ?string {
        if (blank($value)) {
            return null;
        }
        $searchName =
            $this->normalizeReference($value);
        return Province::query()
            ->get([
                'id',
                'nama',
            ])
            ->first(function ($province) use ($searchName) {
                return $this->normalizeReference(
                    $province->nama
                ) === $searchName;
            })
            ?->id;
    }
    /*
    |--------------------------------------------------------------------------
    | CITY
    |--------------------------------------------------------------------------
    */
    private function cityId(
        ?string $value,
        ?string $provinceId = null
    ): ?string {
        if (blank($value)) {
            return null;
        }
        $searchName = $this->normalizeCityName($value);
        if (blank($searchName)) {
            return null;
        }
        $findCity = function ($cities) use ($searchName) {
            return $cities->first(function ($city) use ($searchName) {
                $databaseName = $this->normalizeCityName(
                    $city->nama
                );
                return $databaseName === $searchName
                    || str_replace(' ', '', $databaseName)
                        === str_replace(' ', '', $searchName);
            });
        };
        // Cari berdasarkan provinsi terlebih dahulu.
        if ($provinceId !== null) {
            $city = $findCity(
                City::query()
                    ->where('id_provinsi', $provinceId)
                    ->get(['id', 'id_provinsi', 'nama'])
            );
            if ($city) {
                return (string) $city->id;
            }
        }
        // Fallback jika nama provinsi atau relasinya tidak cocok.
        $city = $findCity(
            City::query()->get(['id', 'id_provinsi', 'nama'])
        );
        return $city ? (string) $city->id : null;
    }
    /*
    |--------------------------------------------------------------------------
    | DISTRICT
    |--------------------------------------------------------------------------
    */
    private function districtId(
        ?string $value,
        ?string $cityId = null
    ): ?string {
        if (blank($value)) {
            return null;
        }
        $searchName = $this->normalizeDistrictName($value);
        if (blank($searchName)) {
            return null;
        }
        $query = District::query();
        if ($cityId !== null) {
            $query->where('id_kota', $cityId);
        }
        $districts = $query->get([
            'id',
            'id_kota',
            'nama',
        ]);
        $district = $districts->first(
            function ($district) use ($searchName) {
                return $this->normalizeDistrictName(
                    $district->nama
                ) === $searchName;
            }
        );
        return $district ? (string) $district->id : null;
    }
    /*
    |--------------------------------------------------------------------------
    | URBAN VILLAGE
    |--------------------------------------------------------------------------
    */
    private function urbanVillageId(
        ?string $value,
        ?string $districtId = null
    ): ?string {
        if (blank($value)) {
            return null;
        }
        $searchName = $this->normalizeUrbanVillageName($value);
        if (blank($searchName)) {
            return null;
        }
        $query = UrbanVillage::query();
        if ($districtId !== null) {
            $query->where('id_kecamatan', $districtId);
        }
        $villages = $query->get([
            'id',
            'id_kecamatan',
            'nama',
        ]);
        $village = $villages->first(
            function ($village) use ($searchName) {
                $databaseName = $this->normalizeUrbanVillageName(
                    $village->nama
                );
                return $databaseName === $searchName
                    || str_replace(' ', '', $databaseName)
                        === str_replace(' ', '', $searchName);
            }
        );
        return $village ? (string) $village->id : null;
    }
    /*
    |--------------------------------------------------------------------------
    | NORMALIZE REFERENCE
    |--------------------------------------------------------------------------
    */
    private function normalizeReference(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }
        $value = trim((string) $value);
        // Hilangkan karakter tersembunyi Excel
        $value = str_replace(
            ["\xc2\xa0", "\u{00A0}"],
            ' ',
            $value
        );
        // Ubah menjadi huruf kapital
        $value = mb_strtoupper($value, 'UTF-8');
        // Samakan tanda baca dengan spasi
        $value = preg_replace(
            '/[.,\/\\\\\-]+/u',
            ' ',
            $value
        );
        // Hanya pertahankan huruf, angka, dan spasi
        $value = preg_replace(
            '/[^\p{L}\p{N}\s]+/u',
            ' ',
            $value
        );
        // Rapikan spasi
        $value = preg_replace(
            '/\s+/u',
            ' ',
            $value
        );
        return trim($value);
    }
    /*
    |--------------------------------------------------------------------------
    | NORMALIZE PROVINCE NAME
    |--------------------------------------------------------------------------
    */
    private function normalizeProvinceName(?string $value): ?string
    {
        $value = $this->string($value);
        return $value !== null
            ? mb_strtoupper($value, 'UTF-8')
            : null;
    }
    /*
    |--------------------------------------------------------------------------
    | NORMALIZE CITY NAME
    |--------------------------------------------------------------------------
    */
    private function normalizeCityName(?string $value): ?string
    {
        $value = $this->string($value);
        if ($value === null) {
            return null;
        }
        $value = mb_strtoupper($value, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', trim($value));
        // Samakan variasi penulisan kabupaten.
        $value = preg_replace(
            '/^(?:KABUTPATEN|KABUPATEN|KAB)\.?\s*/u',
            'KAB. ',
            $value
        );
        // Samakan variasi penulisan kota.
        $value = preg_replace(
            '/^KOTA(?:\s+ADMINISTRASI)?\.?\s*/u',
            'KOTA ',
            $value
        );
        return trim(preg_replace('/\s+/u', ' ', $value));
    }
    /*
    |--------------------------------------------------------------------------
    | NORMALIZE DISTRICT NAME
    |--------------------------------------------------------------------------
    */
    private function normalizeDistrictName(?string $value): ?string
    {
        $value = $this->string($value);
        if ($value === null) {
            return null;
        }
        $value = mb_strtoupper($value, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', trim($value));
        $value = preg_replace(
            '/^(?:KECAMATAN|KEC)\.?\s*/u',
            'KECAMATAN ',
            $value
        );
        return trim(preg_replace('/\s+/u', ' ', $value));
    }
    /*
    |--------------------------------------------------------------------------
    | NORMALIZE URBAN VILLAGE NAME
    |--------------------------------------------------------------------------
    */
    private function normalizeUrbanVillageName(?string $value): ?string
    {
        $value = $this->string($value);
        if ($value === null) {
            return null;
        }
        $value = mb_strtoupper($value, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', trim($value));
        // Pertahankan perbedaan antara KELURAHAN dan DESA.
        $value = preg_replace_callback(
            '/^(KELURAHAN|KEL|DESA)\.?\s*/u',
            static function (array $matches): string {
                $prefix = mb_strtoupper($matches[1], 'UTF-8');
                return $prefix === 'DESA'
                    ? 'DESA '
                    : 'KELURAHAN ';
            },
            $value
        );
        return trim(preg_replace('/\s+/u', ' ', $value));
    }
    /*
    |--------------------------------------------------------------------------
    | TANGGAL PENSIUN
    |--------------------------------------------------------------------------
    */
    private function calculateRetirementDate(
        ?string $birthDate,
        ?Position $position
    ): ?string {
        if (
            blank($birthDate) ||
            !$position ||
            !$position->usia_pensiun
        ) {
            return null;
        }
        return Carbon::parse($birthDate)
            ->addYears(
                (int) $position->usia_pensiun
            )
            ->format('Y-m-d');
    }
    /*
    |--------------------------------------------------------------------------
    | TMT PENSIUN
    |--------------------------------------------------------------------------
    */
    private function calculateRetirementTmt(
        ?string $retirementDate
    ): ?string {
        if (blank($retirementDate)) {
            return null;
        }
        return Carbon::parse($retirementDate)
            ->addMonthNoOverflow()
            ->startOfMonth()
            ->format('Y-m-d');
    }
    /*
    |--------------------------------------------------------------------------
    | TMT PANGKAT BARU
    |--------------------------------------------------------------------------
    */
    private function calculateTmtPangkatBaru(
        ?string $statusPegawai,
        ?string $tmtPangkatLama
    ): ?string {
        if (
            $statusPegawai !== 'PNS' ||
            blank($tmtPangkatLama)
        ) {
            return null;
        }
        return Carbon::parse(
            $tmtPangkatLama
        )
            ->addYears(4)
            ->format('Y-m-d');
    }
    /*
    |--------------------------------------------------------------------------
    | TAMBAH TAHUN
    |--------------------------------------------------------------------------
    */
    private function addYears(
        ?string $date,
        int $years
    ): ?string {
        if (blank($date)) {
            return null;
        }
        return Carbon::parse($date)
            ->addYears($years)
            ->format('Y-m-d');
    }
    /*
    |--------------------------------------------------------------------------
    | MASA KERJA
    |--------------------------------------------------------------------------
    */
    private function calculateMasaKerja(
        ?string $employmentStartDate
    ): array {
        if (blank($employmentStartDate)) {
            return [
                'tahun' => 0,
                'bulan' => 0,
            ];
        }
        $start =
            Carbon::parse(
                $employmentStartDate
            );
        $today =
            Carbon::today();
        if ($start->gt($today)) {
            return [
                'tahun' => 0,
                'bulan' => 0,
            ];
        }
        $diff =
            $start->diff($today);
        return [
            'tahun' => $diff->y,
            'bulan' => $diff->m,
        ];
    }
    /*
    |--------------------------------------------------------------------------
    | UNIQUE VALUE
    |--------------------------------------------------------------------------
    */
    private function uniqueValue(
        ?string $value,
        string $column
    ): ?string {
        if (blank($value)) {
            return null;
        }
        $key =
            $column === 'email'
                ? Str::lower($value)
                : $value;
        switch ($column) {
            case 'nik':
                $cache =& $this->usedNik;
                break;
            case 'email':
                $cache =& $this->usedEmail;
                break;
            case 'no_hp':
                $cache =& $this->usedPhone;
                break;
            default:
                return $value;
        }
        /*
        | Duplikat dalam file.
        */
        if (isset($cache[$key])) {
            return null;
        }
        $query =
            Employee::query();
        /*
        | Duplikat di database.
        */
        if ($column === 'email') {
            $exists =
                $query
                    ->whereRaw(
                        'LOWER(email) = ?',
                        [$key]
                    )
                    ->exists();
        } else {
            $exists =
                $query
                    ->where(
                        $column,
                        $value
                    )
                    ->exists();
        }
        if ($exists) {
            return null;
        }
        $cache[$key] = true;
        return $value;
    }
    /*
    |--------------------------------------------------------------------------
    | STRING
    |--------------------------------------------------------------------------
    */
    private function string(
        $value
    ): ?string {
        if (blank($value)) {
            return null;
        }
        $value =
            trim((string) $value);
        $value =
            preg_replace(
                '/\s+/u',
                ' ',
                $value
            );
        /*
        | Konversi scientific notation Excel.
        */
        if (
            preg_match(
                '/^[0-9]+(\.[0-9]+)?E[+-]?[0-9]+$/i',
                $value
            )
        ) {
            $value =
                sprintf(
                    '%.0f',
                    (float) $value
                );
        }
        return $value;
    }
    /*
    |--------------------------------------------------------------------------
    | TITLE
    |--------------------------------------------------------------------------
    */
    private function title(
        ?string $value
    ): ?string {
        if (blank($value)) {
            return null;
        }
        return Str::title(
            Str::lower(
                trim($value)
            )
        );
    }
    /*
    |--------------------------------------------------------------------------
    | LOWER
    |--------------------------------------------------------------------------
    */
    private function lower(
        ?string $value
    ): ?string {
        if (blank($value)) {
            return null;
        }
        return Str::lower(
            trim($value)
        );
    }
    /*
    |--------------------------------------------------------------------------
    | STATUS PEGAWAI
    |--------------------------------------------------------------------------
    */
    private function normalizeStatusPegawai(
        ?string $value
    ): ?string {
        if (blank($value)) {
            return null;
        }
        return match (
            Str::upper(
                trim($value)
            )
        ) {
            'PNS' => 'PNS',
            'PPPK' => 'PPPK',
            default => null,
        };
    }
    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */
    private function normalizeStatus(
        ?string $value
    ): ?string {
        if (blank($value)) {
            return 'Aktif';
        }
        return match (
            Str::lower(
                trim($value)
            )
        ) {
            'aktif' =>
                'Aktif',
            'nonaktif',
            'non aktif' =>
                'Nonaktif',
            'pensiun' =>
                'Pensiun',
            default =>
                'Aktif',
        };
    }
    /*
    |--------------------------------------------------------------------------
    | AGAMA
    |--------------------------------------------------------------------------
    */
    private function normalizeAgama(
        ?string $value
    ): ?string {
        if (blank($value)) {
            return null;
        }
        return match (
            Str::lower(
                trim($value)
            )
        ) {
            'islam' =>
                'Islam',
            'kristen protestan',
            'protestan',
            'kristen' =>
                'Kristen Protestan',
            'kristen katolik',
            'katolik' =>
                'Kristen Katolik',
            'budha',
            'buddha' =>
                'Budha',
            'hindu' =>
                'Hindu',
            'konghucu' =>
                'Konghucu',
            default =>
                null,
        };
    }
    /*
    |--------------------------------------------------------------------------
    | STATUS NIKAH
    |--------------------------------------------------------------------------
    */
    private function normalizeStatusNikah(
        ?string $value
    ): ?string {
        if (blank($value)) {
            return null;
        }
        return match (
            Str::lower(
                trim($value)
            )
        ) {
            'belum menikah',
            'belum kawin' =>
                'Belum Menikah',
            'menikah',
            'kawin' =>
                'Menikah',
            'cerai hidup' =>
                'Cerai Hidup',
            'cerai mati' =>
                'Cerai Mati',
            default =>
                null,
        };
    }
    /*
    |--------------------------------------------------------------------------
    | JENIS KELAMIN
    |--------------------------------------------------------------------------
    */
    private function normalizeJenisKelamin(
        ?string $value
    ): ?string {
        if (blank($value)) {
            return null;
        }
        return match (
            Str::upper(
                trim($value)
            )
        ) {
            'L',
            'LAKI',
            'LAKI-LAKI',
            'LAKI LAKI',
            'PRIA',
            'MALE' =>
                'Laki-laki',
            'P',
            'PEREMPUAN',
            'WANITA',
            'FEMALE' =>
                'Perempuan',
            default =>
                null,
        };
    }
    /*
    |--------------------------------------------------------------------------
    | DATE
    |--------------------------------------------------------------------------
    */
    private function date(
        $value
    ): ?string {
        if (blank($value)) {
            return null;
        }
        /*
        | Excel serial date.
        */
        if (is_numeric($value)) {
            return ExcelDate::
                excelToDateTimeObject(
                    $value
                )
                ->format('Y-m-d');
        }
        $value =
            trim((string) $value);
        /*
        |--------------------------------------------------------------------------
        | Bahasa Indonesia → Inggris
        |--------------------------------------------------------------------------
        */
        $bulan = [
            'Januari' =>
                'January',
            'Februari' =>
                'February',
            'Maret' =>
                'March',
            'April' =>
                'April',
            'Mei' =>
                'May',
            'Juni' =>
                'June',
            'Juli' =>
                'July',
            'Agustus' =>
                'August',
            'September' =>
                'September',
            'Oktober' =>
                'October',
            'November' =>
                'November',
            'Desember' =>
                'December',
        ];
        $value =
            str_ireplace(
                array_keys($bulan),
                array_values($bulan),
                $value
            );
        /*
        |--------------------------------------------------------------------------
        | FORMAT TANGGAL
        |--------------------------------------------------------------------------
        */
        $formats = [
            'd F Y',
            'j F Y',
            'd M Y',
            'j M Y',
            'Y-m-d',
            'd/m/Y',
            'j/n/Y',
            'd-m-Y',
            'j-n-Y',
        ];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat(
                    $format,
                    $value
                )->format('Y-m-d');
            } catch (\Throwable) {
                //
            }
        }
        /*
        |--------------------------------------------------------------------------
        | Fallback Carbon
        |--------------------------------------------------------------------------
        */
        try {
            return Carbon::parse(
                $value
            )->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
    /*
    |--------------------------------------------------------------------------
    | PHONE
    |--------------------------------------------------------------------------
    */
    private function normalizePhone(
        ?string $phone
    ): ?string {
        if (blank($phone)) {
            return null;
        }
        $phone =
            preg_replace(
                '/\D+/',
                '',
                trim($phone)
            );
        /*
        | 62xxxxxxxx → 08xxxxxxxx
        */
        if (
            str_starts_with(
                $phone,
                '62'
            )
        ) {
            $phone =
                '0' .
                substr(
                    $phone,
                    2
                );
        }
        /*
        | 8xxxxxxxx → 08xxxxxxxx
        */
        if (
            str_starts_with(
                $phone,
                '8'
            )
        ) {
            $phone =
                '0' . $phone;
        }
        if (
            !str_starts_with(
                $phone,
                '0'
            )
        ) {
            return null;
        }
        if (
            !preg_match(
                '/^08[1-9][0-9]{7,11}$/',
                $phone
            )
        ) {
            return null;
        }
        return $phone;
    }
}

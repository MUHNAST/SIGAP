<?php
namespace Database\Seeders;
use App\Models\City;
use App\Models\Department;
use App\Models\District;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Pppk;
use App\Models\Province;
use App\Models\Rank;
use App\Models\UrbanVillage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
class EmployeeSeeder extends Seeder
{
    /**
     * Jalankan seeder.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Unit Kerja
        |--------------------------------------------------------------------------
        */
        $department = Department::where('kode', 'K-PNC')->first();
        if (! $department) {
            throw new RuntimeException(
                'Kelurahan Poncol belum tersedia. Jalankan DepartmentSeeder terlebih dahulu.'
            );
        }
        /*
        |--------------------------------------------------------------------------
        | Golongan PPPK
        |--------------------------------------------------------------------------
        */
        $pppk = Pppk::where('kode', '0')->first();
        if (! $pppk) {
            throw new RuntimeException(
                'Data Bukan PPPK belum tersedia. Jalankan PppkSeeder terlebih dahulu.'
            );
        }
        // /*
        // |--------------------------------------------------------------------------
        // | Wilayah Jawa Tengah - Kota Pekalongan
        // |--------------------------------------------------------------------------
        // */
        // $province = Province::where('nama', 'Jawa Tengah')->first();
        // if (! $province) {
        //     throw new RuntimeException(
        //         'Provinsi Jawa Tengah tidak ditemukan. Periksa data WilayahSeeder.'
        //     );
        // }
        // $city = City::where('id_provinsi', $province->id)
        //     ->where('nama', 'Kota Pekalongan')
        //     ->first();
        // if (! $city) {
        //     throw new RuntimeException(
        //         'Kota Pekalongan tidak ditemukan. Periksa data WilayahSeeder.'
        //     );
        // }
        // $district = District::where('id_kota', $city->id)
        //     ->where('nama', 'Pekalongan Timur')
        //     ->first();
        // if (! $district) {
        //     throw new RuntimeException(
        //         'Kecamatan Pekalongan Timur tidak ditemukan. Periksa data WilayahSeeder.'
        //     );
        // }
        // $urbanVillage = UrbanVillage::where(
        //     'id_kecamatan',
        //     $district->id
        // )->where('nama', 'Poncol')->first();
        // if (! $urbanVillage) {
        //     throw new RuntimeException(
        //         'Kelurahan Poncol tidak ditemukan di Kecamatan Pekalongan Timur.'
        //     );
        // }
        /*
        |--------------------------------------------------------------------------
        | Pendidikan
        |--------------------------------------------------------------------------
        */
        $educationLevel = EducationLevel::where('kode', 'S1')->first();
        if (! $educationLevel) {
            throw new RuntimeException(
                'Pendidikan S1 belum tersedia. Jalankan EducationLevelSeeder terlebih dahulu.'
            );
        }
        /*
        |--------------------------------------------------------------------------
        | Pangkat
        |--------------------------------------------------------------------------
        */
        $rank = Rank::where('kode', 'III/a')->first();
        if (! $rank) {
            throw new RuntimeException(
                'Pangkat III/a belum tersedia. Jalankan RankSeeder terlebih dahulu.'
            );
        }
        /*
        |--------------------------------------------------------------------------
        | Jabatan
        |--------------------------------------------------------------------------
        */
        $positionKepegawaian = Position::where(
            'kode',
            'J-ADMIN-KEPEG'
        )->first();
        $positionUmum = Position::where(
            'kode',
            'J-ADMIN-UMUM'
        )->first();
        if (! $positionKepegawaian || ! $positionUmum) {
            throw new RuntimeException(
                'Jabatan contoh belum tersedia. Jalankan PositionSeeder terlebih dahulu.'
            );
        }
        /*
        |--------------------------------------------------------------------------
        | Employee 1
        |--------------------------------------------------------------------------
        */
        Employee::updateOrCreate(
            [
                'nip' => '199501012020011001',
            ],
            [
                'nik' => '3301010101950001',
                'email' => 'admin.poncol@example.com',
                'no_hp' => '081234567890',
                'id_pangkat' => $rank->id,
                'kode_pppk' => $pppk->kode,
                'kode_unit_kerja' => $department->kode,
                'kode_jabatan' => $positionKepegawaian->kode,
                'nama' => 'Admin Kelurahan Contoh',
                'tingkat_pendidikan' => $educationLevel->kode,
                // 'tempat_lahir' => $city->id,
                'tanggal_lahir' => '1995-01-01',
                'jenis_kelamin' => 'Laki-laki',
                'agama' => 'Islam',
                'alamat' => 'Kelurahan Poncol, Pekalongan Timur',
                // 'id_provinsi' => $province->id,
                // 'id_kota' => $city->id,
                // 'id_kecamatan' => $district->id,
                // 'id_kelurahan' => $urbanVillage->id,
                'status_pegawai' => 'PNS',
                'status_nikah' => 'Belum Menikah',
                'tanggal_masuk' => '2020-01-01',
                'tmt_pangkat_lama' => '2023-04-01',
                'tmt_pangkat_baru' => '2027-04-01',
                'tmt_kgb_lama' => '2025-04-01',
                'tmt_kgb_baru' => '2027-04-01',
                'tanggal_pensiun' => '2053-01-01',
                'tmt_pensiun' => '2053-02-01',
                'foto' => 'images/default-user.png',
                'tahun_masa_kerja' => 6,
                'bulan_masa_kerja' => 0,
                'status' => 'Aktif',
            ]
        );
        /*
        |--------------------------------------------------------------------------
        | Employee 2
        |--------------------------------------------------------------------------
        */
        $employee = Employee::updateOrCreate(
            [
                'nip' => '198501012010011001',
            ],
            [
                'nik' => '3301010101850001',
                'email' => 'pegawai.poncol@example.com',
                'no_hp' => '081234567891',
                'id_pangkat' => $rank->id,
                'kode_pppk' => $pppk->kode,
                'kode_unit_kerja' => $department->kode,
                'kode_jabatan' => $positionUmum->kode,
                'nama' => 'Pegawai Contoh',
                'tingkat_pendidikan' => $educationLevel->kode,
                // 'tempat_lahir' => $city->id,
                'tanggal_lahir' => '1985-01-01',
                'jenis_kelamin' => 'Perempuan',
                'agama' => 'Islam',
                'alamat' => 'Kelurahan Poncol, Pekalongan Timur',
                // 'id_provinsi' => $province->id,
                // 'id_kota' => $city->id,
                // 'id_kecamatan' => $district->id,
                // 'id_kelurahan' => $urbanVillage->id,
                'status_pegawai' => 'PNS',
                'status_nikah' => 'Menikah',
                'tanggal_masuk' => '2010-01-01',
                'tmt_pangkat_lama' => '2023-04-01',
                'tmt_pangkat_baru' => '2027-04-01',
                'tmt_kgb_lama' => '2025-04-01',
                'tmt_kgb_baru' => '2027-04-01',
                'tanggal_pensiun' => '2043-01-01',
                'tmt_pensiun' => '2043-02-01',
                'foto' => 'images/default-user.png',
                'tahun_masa_kerja' => 16,
                'bulan_masa_kerja' => 0,
                'status' => 'Aktif',
            ]
        );

        User::updateOrCreate(
            [
                'nip' => $employee->nip,
            ],
            [
                'username' => $employee->nip,
                'kode_unit_kerja' => $employee->kode_unit_kerja,
                'password' => Hash::make('123456789'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );
    }
}

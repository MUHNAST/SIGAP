<?php
namespace Database\Seeders;
use App\Models\EducationLevel;
use Illuminate\Database\Seeder;
class EducationLevelSeeder extends Seeder
{
    /**
     * Jalankan seeder.
     */
    public function run(): void
    {
        $educations = [
            [
                'kode' => 'SD',
                'nama' => 'SD',
                'pangkat_pns_minimum' => 1, // I/a
                'pangkat_pns_maksimum' => 5, // II/a
                'golongan_pppk_minimum' => 1, // I
                'golongan_pppk_maksimum' => 3, // I
                'deskripsi' => 'Sekolah Dasar atau sederajat.',
                'status' => true,
            ],
            [
                'kode' => 'SMP',
                'nama' => 'SMP/SLTP',
                'pangkat_pns_minimum' => 3, // I/c
                'pangkat_pns_maksimum' => 7, // II/c
                'golongan_pppk_minimum' => 4, // IV
                'golongan_pppk_maksimum' => 4, // IV
                'deskripsi' => 'Sekolah Menengah Pertama atau sederajat.',
                'status' => true,
            ],
            [
                'kode' => 'SMP_KEJURUAN',
                'nama' => 'SMP Kejuruan/SLTP Kejuruan',
                'pangkat_pns_minimum' => 3, // I/c
                'pangkat_pns_maksimum' => 8, // II/d
                'golongan_pppk_minimum' => 4, // IV
                'golongan_pppk_maksimum' => 4, // IV
                'deskripsi' => 'Sekolah Menengah Pertama atau pendidikan kejuruan yang sederajat sesuai ketentuan.',
                'status' => true,
            ],
            [
                'kode' => 'SMA',
                'nama' => 'SLTA/SLTA KEJURUAN/SMA/SMK/MA/Sederajat',
                'pangkat_pns_minimum' => 5, // II/a
                'pangkat_pns_maksimum' => 10, // III/b
                'golongan_pppk_minimum' => 5, // V
                'golongan_pppk_maksimum' => 5, // V
                'deskripsi' => 'Sekolah Menengah Atas, Sekolah Menengah Kejuruan, Madrasah Aliyah, atau sederajat.',
                'status' => true,
            ],
            [
                'kode' => 'D1',
                'nama' => 'Diploma I',
                'pangkat_pns_minimum' => 5, // II/a
                'pangkat_pns_maksimum' => 10, // III/b
                'golongan_pppk_minimum' => 5, // V
                'golongan_pppk_maksimum' => 5, // V
                'deskripsi' => 'Pendidikan Diploma I atau sederajat.',
                'status' => true,
            ],
            [
                'kode' => 'D2',
                'nama' => 'Diploma II',
                'pangkat_pns_minimum' => 6, // II/b
                'pangkat_pns_maksimum' => 10, // III/b
                'golongan_pppk_minimum' => 6, // VI
                'golongan_pppk_maksimum' => 6, // VI
                'deskripsi' => 'Pendidikan Diploma II atau sederajat.',
                'status' => true,
            ],
            [
                'kode' => 'SGPLB',
                'nama' => 'SGPLB',
                'pangkat_pns_minimum' => 6, // II/b
                'pangkat_pns_maksimum' => 11, // III/c
                'golongan_pppk_minimum' => 6, // VI
                'golongan_pppk_maksimum' => 6, // VI
                'deskripsi' => 'Pendidikan Diploma II atau sederajat.',
                'status' => true,
            ],
            [
                'kode' => 'D3',
                'nama' => 'Diploma III',
                'pangkat_pns_minimum' => 7, // II/c
                'pangkat_pns_maksimum' => 11, // III/c
                'golongan_pppk_minimum' => 7, // VII
                'golongan_pppk_maksimum' => 8, // VIII
                'deskripsi' => 'Pendidikan Diploma III atau sederajat.',
                'status' => true,
            ],
            [
                'kode' => 'D4',
                'nama' => 'Diploma IV',
                'pangkat_pns_minimum' => 9, // III/a
                'pangkat_pns_maksimum' => 12, // III/d
                'golongan_pppk_minimum' => 9, // IX
                'golongan_pppk_maksimum' => 9, // IX
                'deskripsi' => 'Pendidikan Diploma IV atau sederajat.',
                'status' => true,
            ],
            [
                'kode' => 'S1',
                'nama' => 'Sarjana (S1)',
                'pangkat_pns_minimum' => 9, // III/a
                'pangkat_pns_maksimum' => 12, // III/d
                'golongan_pppk_minimum' => 9, // IX
                'golongan_pppk_maksimum' => 9, // IX
                'deskripsi' => 'Pendidikan Sarjana (S1) atau sederajat.',
                'status' => true,
            ],
            [
                'kode' => 'S2',
                'nama' => 'Magister (S2)',
                'pangkat_pns_minimum' => 10, // III/b
                'pangkat_pns_maksimum' => 13, // IV/a
                'golongan_pppk_minimum' => 10, // X
                'golongan_pppk_maksimum' => 10, // X
                'deskripsi' => 'Pendidikan Magister (S2) atau sederajat.',
                'status' => true,
            ],
            [
                'kode' => 'S3',
                'nama' => 'Doktor (S3)',
                'pangkat_pns_minimum' => 11, // III/c
                'pangkat_pns_maksimum' => 14, // IV/b
                'golongan_pppk_minimum' => 11, // XI
                'golongan_pppk_maksimum' => 11, // XI
                'deskripsi' => 'Pendidikan Doktor (S3) atau sederajat.',
                'status' => true,
            ],
        ];
        foreach ($educations as $education) {
            EducationLevel::updateOrCreate(
                ['kode' => $education['kode']],
                $education
            );
        }
    }
}

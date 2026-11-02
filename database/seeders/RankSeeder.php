<?php
namespace Database\Seeders;
use App\Models\Rank;
use Illuminate\Database\Seeder;

class RankSeeder extends Seeder
{
    public function run(): void
    {
        $ranks = [
            ['kode' => '0',     'nama' => 'Bukan PNS',               'urutan_pangkat' => 0],
            ['kode' => 'I/a',   'nama' => 'Juru Muda',               'urutan_pangkat' => 1],
            ['kode' => 'I/b',   'nama' => 'Juru Muda Tingkat I',     'urutan_pangkat' => 2],
            ['kode' => 'I/c',   'nama' => 'Juru',                    'urutan_pangkat' => 3],
            ['kode' => 'I/d',   'nama' => 'Juru Tingkat I',          'urutan_pangkat' => 4],
            ['kode' => 'II/a',  'nama' => 'Pengatur Muda',           'urutan_pangkat' => 5],
            ['kode' => 'II/b',  'nama' => 'Pengatur Muda Tingkat I', 'urutan_pangkat' => 6],
            ['kode' => 'II/c',  'nama' => 'Pengatur',                'urutan_pangkat' => 7],
            ['kode' => 'II/d',  'nama' => 'Pengatur Tingkat I',      'urutan_pangkat' => 8],
            ['kode' => 'III/a', 'nama' => 'Penata Muda',             'urutan_pangkat' => 9],
            ['kode' => 'III/b', 'nama' => 'Penata Muda Tingkat I',   'urutan_pangkat' => 10],
            ['kode' => 'III/c', 'nama' => 'Penata',                  'urutan_pangkat' => 11],
            ['kode' => 'III/d', 'nama' => 'Penata Tingkat I',        'urutan_pangkat' => 12],
            ['kode' => 'IV/a',  'nama' => 'Pembina',                 'urutan_pangkat' => 13],
            ['kode' => 'IV/b',  'nama' => 'Pembina Tingkat I',       'urutan_pangkat' => 14],
            ['kode' => 'IV/c',  'nama' => 'Pembina Utama Muda',      'urutan_pangkat' => 15],
            ['kode' => 'IV/d',  'nama' => 'Pembina Utama Madya',     'urutan_pangkat' => 16],
            ['kode' => 'IV/e',  'nama' => 'Pembina Utama',           'urutan_pangkat' => 17],
        ];
        foreach ($ranks as $rank) {
            Rank::updateOrCreate(
                ['kode' => $rank['kode']],
                $rank
            );
        }
    }
}

// namaspace Database\Seeders;
// use App\Models\Rank;
// use Illuminate\Database\Seeder;
// class RankSeeder extends Seeder
// {
//     public function run(): void
//     {
//         $ranks = [
//             ['kode' => 'I/a',   'nama' => 'Juru Muda'],
//             ['kode' => 'I/b',   'nama' => 'Juru Muda Tingkat I'],
//             ['kode' => 'I/c',   'nama' => 'Juru'],
//             ['kode' => 'I/d',   'nama' => 'Juru Tingkat I'],
//             ['kode' => 'II/a',  'nama' => 'Pengatur Muda'],
//             ['kode' => 'II/b',  'nama' => 'Pengatur Muda Tingkat I'],
//             ['kode' => 'II/c',  'nama' => 'Pengatur'],
//             ['kode' => 'II/d',  'nama' => 'Pengatur Tingkat I'],
//             ['kode' => 'III/a', 'nama' => 'Penata Muda'],
//             ['kode' => 'III/b', 'nama' => 'Penata Muda Tingkat I'],
//             ['kode' => 'III/c', 'nama' => 'Penata'],
//             ['kode' => 'III/d', 'nama' => 'Penata Tingkat I'],
//             ['kode' => 'IV/a',  'nama' => 'Pembina'],
//             ['kode' => 'IV/b',  'nama' => 'Pembina Tingkat I'],
//             ['kode' => 'IV/c',  'nama' => 'Pembina Utama Muda'],
//             ['kode' => 'IV/d',  'nama' => 'Pembina Utama Madya'],
//             ['kode' => 'IV/e',  'nama' => 'Pembina Utama'],
//         ];
//         foreach ($ranks as $rank) {
//             Rank::updateOrCreate(
//                 ['kode' => $rank['kode']],
//                 ['nama' => $rank['nama']]
//             );
//         }
//     }
// }

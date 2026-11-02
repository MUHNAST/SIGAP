<?php

namespace Database\Seeders;

use App\Models\Pppk;
use App\Models\PppkGrade;
use Illuminate\Database\Seeder;

class PppkSeeder extends Seeder
{
    /**
     * Jalankan seeder.
     */
    public function run(): void
    {
        $grades = [
            [
                'kode' => '0',
                'nama' => 'Bukan PPPK',
                'urutan_golongan' => 0,
            ],
            [
                'kode' => 'I',
                'nama' => 'Golongan I',
                'urutan_golongan' => 1,
            ],
            [
                'kode' => 'II',
                'nama' => 'Golongan II',
                'urutan_golongan' => 2,
            ],
            [
                'kode' => 'III',
                'nama' => 'Golongan III',
                'urutan_golongan' => 3,
            ],
            [
                'kode' => 'IV',
                'nama' => 'Golongan IV',
                'urutan_golongan' => 4,
            ],
            [
                'kode' => 'V',
                'nama' => 'Golongan V',
                'urutan_golongan' => 5,
            ],
            [
                'kode' => 'VI',
                'nama' => 'Golongan VI',
                'urutan_golongan' => 6,
            ],
            [
                'kode' => 'VII',
                'nama' => 'Golongan VII',
                'urutan_golongan' => 7,
            ],
            [
                'kode' => 'VIII',
                'nama' => 'Golongan VIII',
                'urutan_golongan' => 8,
            ],
            [
                'kode' => 'IX',
                'nama' => 'Golongan IX',
                'urutan_golongan' => 9,
            ],
            [
                'kode' => 'X',
                'nama' => 'Golongan X',
                'urutan_golongan' => 10,
            ],
            [
                'kode' => 'XI',
                'nama' => 'Golongan XI',
                'urutan_golongan' => 11,
            ],
            [
                'kode' => 'XII',
                'nama' => 'Golongan XII',
                'urutan_golongan' => 12,
            ],
            [
                'kode' => 'XIII',
                'nama' => 'Golongan XIII',
                'urutan_golongan' => 13,
            ],
            [
                'kode' => 'XIV',
                'nama' => 'Golongan XIV',
                'urutan_golongan' => 14,
            ],
            [
                'kode' => 'XV',
                'nama' => 'Golongan XV',
                'urutan_golongan' => 15,
            ],
            [
                'kode' => 'XVI',
                'nama' => 'Golongan XVI',
                'urutan_golongan' => 16,
            ],
            [
                'kode' => 'XVII',
                'nama' => 'Golongan XVII',
                'urutan_golongan' => 17,
            ],
        ];

        foreach ($grades as $grade) {
            Pppk::updateOrCreate(
                ['kode' => $grade['kode']],
                $grade
            );
        }
    }
}

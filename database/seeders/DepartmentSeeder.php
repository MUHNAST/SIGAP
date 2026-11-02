<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Jalankan seeder.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Unit Kerja Kecamatan Pekalongan Timur
        |--------------------------------------------------------------------------
        */

        $departments = [
            [
                'kode' => 'KC-PT',
                'nama' => 'Kecamatan Pekalongan Timur',
                'deskripsi' => 'Unit kerja Kecamatan Pekalongan Timur',
                'status' => true,
            ],
            [
                'kode' => 'K-NYT',
                'nama' => 'Kelurahan Noyontaansari',
                'deskripsi' => 'Unit kerja Kelurahan Noyontaansari',
                'status' => true,
            ],
            [
                'kode' => 'K-KMN',
                'nama' => 'Kelurahan Kauman',
                'deskripsi' => 'Unit kerja Kelurahan Kauman',
                'status' => true,
            ],
            [
                'kode' => 'K-PNC',
                'nama' => 'Kelurahan Poncol',
                'deskripsi' => 'Unit kerja Kelurahan Poncol',
                'status' => true,
            ],
            [
                'kode' => 'K-KLG',
                'nama' => 'Kelurahan Klego',
                'deskripsi' => 'Unit kerja Kelurahan Klego',
                'status' => true,
            ],
            [
                'kode' => 'K-GMR',
                'nama' => 'Kelurahan Gamer',
                'deskripsi' => 'Unit kerja Kelurahan Gamer',
                'status' => true,
            ],
            [
                'kode' => 'K-STN',
                'nama' => 'Kelurahan Setono',
                'deskripsi' => 'Unit kerja Kelurahan Setono',
                'status' => true,
            ],
            [
                'kode' => 'K-KBR',
                'nama' => 'Kelurahan Kali Baros',
                'deskripsi' => 'Unit kerja Kelurahan Kali Baros',
                'status' => true,
            ],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['kode' => $department['kode']],
                $department
            );
        }
    }
}

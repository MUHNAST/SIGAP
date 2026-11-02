<?php

// namespace Database\Seeders;

// use App\Models\Employee;
// use App\Models\KgbHistory;
// use Illuminate\Database\Seeder;

// class KgbHistorySeeder extends Seeder
// {
//     public function run(): void
//     {
//         KgbHistory::truncate();

//         Employee::each(function ($employee) {

//             if (!$employee->tmt_kgb_lama) {
//                 return;
//             }

//             $masaKerjaTahun = $employee->masa_kerja_tahun ?? 0;
//             $masaKerjaBulan = $employee->masa_kerja_bulan ?? 0;

//             KgbHistory::create([
//                 'employee_id'              => $employee->id,

//                 'nomor_sk_lama'            => fake()->numerify('800/###/2024'),
//                 'nomor_sk_baru'            => fake()->numerify('800/###/2026'),

//                 'tanggal_sk_lama'          => $employee->tmt_kgb_lama,
//                 'tanggal_sk_baru'          => $employee->tmt_kgb_baru,

//                 'pejabat'                  => 'Camat',

//                 'tmt_kgb_lama'             => $employee->tmt_kgb_lama,
//                 'tmt_kgb_baru'             => $employee->tmt_kgb_baru,

//                 'masa_kerja_tahun_lama'    => max(0, $masaKerjaTahun - 2),
//                 'masa_kerja_bulan_lama'    => $masaKerjaBulan,

//                 'masa_kerja_tahun_baru'    => $masaKerjaTahun,
//                 'masa_kerja_bulan_baru'    => $masaKerjaBulan,

//                 'id_pangkat'               => $employee->id_pangkat,

//                 'gaji_lama'                => fake()->numberBetween(3000000, 6000000),
//                 'gaji_baru'                => fake()->numberBetween(6000000, 9000000),

//                 'created_by'               => $employee->id_user,
//             ]);
//         });
//     }
// }

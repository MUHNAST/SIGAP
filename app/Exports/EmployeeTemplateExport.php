<?php
namespace App\Exports;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
class EmployeeTemplateExport implements FromCollection, WithHeadings
{
    public function collection(): Collection
    {
        return new Collection([
            [
                // DATA PEGAWAI
                'Budi Santoso',                 // Nama
                '3375010101010001',             // NIK
                '198912312024001001',           // NIP
                'pegawai.pns@example.com',      // Email
                '081234567890',                 // No. HP
                'PNS',                          // Status Pegawai
                // PANGKAT / GOLONGAN
                'Juru Muda',                    // Pangkat PNS
                '',                             // Golongan PPPK
                // KEPEGAWAIAN
                'Camat',                        // Jabatan
                'Kecamatan Pekalongan Timur',   // Unit Kerja
                'S1',                           // Pendidikan
                // DATA PRIBADI
                'KAB. PEKALONGAN',              // Tempat Lahir
                '1990-01-01',                   // Tanggal Lahir
                'Laki-laki',                    // Jenis Kelamin
                'Islam',                        // Agama
                'Menikah',                      // Status Pernikahan
                // WILAYAH
                'Jawa Tengah',                  // Provinsi
                'KAB. Pekalongan',              // Kota/Kabupaten
                'BOJONG',                       // Kecamatan
                'BABALAN KIDUL',                // Kelurahan/Desa
                'BABALAN KIDUL',                // Alamat
                // TANGGAL MASUK
                '2020-01-01',                   // Tanggal Masuk
                '5 TAHUN 3 BULAN',              // Masa Kerja
                // TMT PANGKAT
                '2024-01-01',                   // TMT Pangkat Lama
                '2028-01-01',                   // TMT Pangkat Baru
                // TMT KGB
                '2024-01-01',                   // TMT KGB Lama
                '2026-01-01',                   // TMT KGB Baru
                // PENSIUN
                '2048-01-01',                   // Tanggal Pensiun
                '2048-02-01',                   // TMT Pensiun
                // STATUS
                'Aktif',                        // Status
            ],
            [
                // DATA PEGAWAI
                'Siti Aminah',                  // Nama
                '3375010102020002',             // NIK
                '199001012024002002',           // NIP
                'pegawai.pppk@example.com',     // Email
                '081298765432',                 // No. HP
                'PPPK',                         // Status Pegawai
                // PANGKAT / GOLONGAN
                '',                             // Pangkat PNS
                'Golongan IX',                  // Golongan PPPK
                // KEPEGAWAIAN
                'Staff Pelayanan',              // Jabatan
                'Kelurahan Poncol',             // Unit Kerja
                'S1',                           // Pendidikan
                // DATA PRIBADI
                'KOTA PEKALONGAN',              // Tempat Lahir
                '1990-02-02',                   // Tanggal Lahir
                'Perempuan',                    // Jenis Kelamin
                'Islam',                        // Agama
                'Menikah',                      // Status Pernikahan
                // WILAYAH
                'Jawa Tengah',                  // Provinsi
                'KOTA Pekalongan',              // Kota/Kabupaten
                'Pekalongan Timur',             // Kecamatan
                'Setono',                       // Kelurahan/Desa
                'Jl. Diponegoro No. 10',        // Alamat
                // TANGGAL MASUK
                '2021-01-01',                   // Tanggal Masuk
                '10 TAHUN 2 BULAN',             // Masa Kerja
                // TMT PANGKAT
                '',                             // TMT Pangkat Lama
                '',                             // TMT Pangkat Baru
                // TMT KGB
                '2025-01-01',                   // TMT KGB Lama
                '2027-01-01',                   // TMT KGB Baru
                // PENSIUN
                '2048-02-01',                   // Tanggal Pensiun
                '2048-03-01',                   // TMT Pensiun
                // STATUS
                'Aktif',                        // Status
            ],
        ]);
    }
    public function headings(): array
    {
        return [
            // DATA PEGAWAI
            'Nama',
            'NIK',
            'NIP',
            'Email',
            'no_hp',
            'Status Pegawai',
            // PANGKAT / GOLONGAN
            'Pangkat PNS',
            'Golongan PPPK',
            // KEPEGAWAIAN
            'Jabatan',
            'Unit Kerja',
            'Pendidikan',
            // DATA PRIBADI
            'Tempat Lahir',
            'Tanggal Lahir',
            'Jenis Kelamin',
            'Agama',
            'Status Pernikahan',
            // WILAYAH
            'Provinsi',
            'Kota/Kabupaten',
            'Kecamatan',
            'Kelurahan/Desa',
            'Alamat',
            // TANGGAL MASUK
            'Tanggal Masuk',
            'Masa Kerja',
            // TMT PANGKAT
            'TMT Pangkat Lama',
            'TMT Pangkat Baru',
            // TMT KGB
            'TMT KGB Lama',
            'TMT KGB Baru',
            // PENSIUN
            'Tanggal Pensiun',
            'TMT Pensiun',
            // STATUS
            'Status',
        ];
    }
}

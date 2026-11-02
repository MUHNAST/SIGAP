<?php
namespace Database\Seeders;
use App\Models\Position;
use Illuminate\Database\Seeder;
class PositionSeeder extends Seeder
{
    /**
     * Jalankan seeder.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Jabatan Kecamatan dan Kelurahan
        |--------------------------------------------------------------------------
        */
        $positions = [
            // Jabatan struktural kecamatan
            [
                'kode' => 'J-CAMAT',
                'nama' => 'Camat',
                'deskripsi' => 'Memimpin penyelenggaraan pemerintahan Kecamatan Pekalongan Timur.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-SEKCAM',
                'nama' => 'Sekretaris Kecamatan',
                'deskripsi' => 'Mengoordinasikan pelaksanaan administrasi dan kesekretariatan kecamatan.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-KASUB-UMPEG',
                'nama' => 'Kepala Subbagian Umum dan Kepegawaian',
                'deskripsi' => 'Mengelola urusan umum, kepegawaian, dan administrasi perkantoran.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-KASUB-REK',
                'nama' => 'Kepala Subbagian Perencanaan Evaluasi dan Keuangan',
                'deskripsi' => 'Mengelola perencanaan, evaluasi, pelaporan, dan administrasi keuangan.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-KASI-PEMTRANTIB',
                'nama' => 'Kepala Seksi Tata Pemerintahan, Ketentraman dan Ketertiban Umum',
                'deskripsi' => 'Mengelola urusan tata pemerintahan, ketentraman, dan ketertiban umum.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-KASI-PEMBANGUNAN',
                'nama' => 'Kepala Seksi Pembangunan, Pemberdayaan Masyarakat dan Kesejahteraan Sosial',
                'deskripsi' => 'Mengelola urusan pembangunan, pemberdayaan masyarakat, dan kesejahteraan sosial.',
                'usia_pensiun' => 58,
            ],
            // Jabatan struktural kelurahan
            [
                'kode' => 'J-LURAH',
                'nama' => 'Lurah',
                'deskripsi' => 'Memimpin penyelenggaraan pemerintahan dan pelayanan masyarakat di kelurahan.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-SEKEL',
                'nama' => 'Sekretaris Kelurahan',
                'deskripsi' => 'Mengoordinasikan administrasi dan kesekretariatan kelurahan.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-KASI-PEMKEL',
                'nama' => 'Kepala Seksi Pemerintahan, Ketentraman, Ketertiban dan Perlindungan Masyarakat',
                'deskripsi' => 'Mengelola urusan pemerintahan, ketentraman, ketertiban, dan perlindungan masyarakat di kelurahan.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-KASI-KESMAS',
                'nama' => 'Kepala Seksi Pembangunan dan Kesejahteraan Masyarakat',
                'deskripsi' => 'Mengelola urusan pembangunan dan kesejahteraan masyarakat di kelurahan.',
                'usia_pensiun' => 58,
            ],
            // Jabatan pelaksana dan administrasi
            [
                'kode' => 'J-PENGELOLA-KEPEG',
                'nama' => 'Pengelola Kepegawaian',
                'deskripsi' => 'Mengelola data dan administrasi kepegawaian.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-ADMIN-KEPEG',
                'nama' => 'Pengadministrasi Kepegawaian',
                'deskripsi' => 'Melaksanakan pencatatan dan pengadministrasian dokumen kepegawaian.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-PENGELOLA-KEU',
                'nama' => 'Pengelola Keuangan',
                'deskripsi' => 'Melaksanakan pengelolaan administrasi keuangan.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-BENDAHARA',
                'nama' => 'Bendahara',
                'deskripsi' => 'Melaksanakan pengelolaan dan pencatatan transaksi keuangan sesuai tugasnya.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-PROGRAM-LAP',
                'nama' => 'Pengelola Program dan Laporan',
                'deskripsi' => 'Mengelola data program kerja dan penyusunan laporan kegiatan.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-ADMIN-RENCANA',
                'nama' => 'Pengadministrasi Perencanaan dan Program',
                'deskripsi' => 'Melaksanakan administrasi perencanaan dan program kerja.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-ADMIN-KEU',
                'nama' => 'Pengadministrasi Keuangan',
                'deskripsi' => 'Melaksanakan pencatatan dan administrasi keuangan.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-PENGELOLA-PEM',
                'nama' => 'Pengelola Administrasi Pemerintahan',
                'deskripsi' => 'Mengelola administrasi penyelenggaraan pemerintahan.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-PENGELOLA-KAMTIB',
                'nama' => 'Pengelola Keamanan dan Ketertiban',
                'deskripsi' => 'Mengelola administrasi urusan keamanan dan ketertiban.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-KELOLA-PMBRDAYAAN',
                'nama' => 'Pengelola Pemberdayaan Masyarakat',
                'deskripsi' => 'Mengelola administrasi kegiatan pemberdayaan masyarakat.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-PENGELOLA-KESOS',
                'nama' => 'Pengelola Kesejahteraan Sosial',
                'deskripsi' => 'Mengelola administrasi kegiatan kesejahteraan sosial.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-ADMIN-PEM',
                'nama' => 'Pengadministrasi Pemerintahan',
                'deskripsi' => 'Melaksanakan administrasi urusan pemerintahan.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-ADMIN-UMUM',
                'nama' => 'Pengadministrasi Umum',
                'deskripsi' => 'Melaksanakan administrasi umum dan tata usaha.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-ADMIN-SARPRAS',
                'nama' => 'Pengadministrasi Sarana dan Prasarana',
                'deskripsi' => 'Melaksanakan administrasi sarana dan prasarana kantor.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-PENGELOLA-SARPRAS',
                'nama' => 'Pengelola Sarana dan Prasarana Kantor',
                'deskripsi' => 'Mengelola inventaris dan sarana prasarana kantor.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-PENGELOLA-BMD',
                'nama' => 'Pengelola Pemanfaatan Barang Milik Daerah',
                'deskripsi' => 'Mengelola administrasi pemanfaatan barang milik daerah.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-PENGELOLA-DATA',
                'nama' => 'Pengelola Data',
                'deskripsi' => 'Mengelola dan memperbarui data untuk mendukung pelaksanaan pekerjaan.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-ARSIPARIS',
                'nama' => 'Arsiparis',
                'deskripsi' => 'Melaksanakan pengelolaan arsip sesuai tugas dan jenjang jabatan.',
                'usia_pensiun' => 58,
            ],
            [
                'kode' => 'J-PRAMU-BAKTI',
                'nama' => 'Pramu Bakti',
                'deskripsi' => 'Melaksanakan tugas pelayanan dan dukungan operasional perkantoran.',
                'usia_pensiun' => 58,
            ],
        ];
        foreach ($positions as $position) {
            Position::updateOrCreate(
                ['kode' => $position['kode']],
                $position
            );
        }
    }
}

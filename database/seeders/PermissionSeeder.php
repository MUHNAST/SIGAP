<?php
namespace Database\Seeders;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Pengguna
            ['Melihat Data Pengguna', 'user.view', 'user'],
            ['Mereset Password Pengguna', 'user.reset_password', 'user'],
            // Pegawai
            ['Melihat Data Pegawai', 'pegawai.view', 'pegawai'],
            ['Menambah Pegawai', 'pegawai.create', 'pegawai'],
            ['Mengubah Data Pegawai', 'pegawai.update', 'pegawai'],
            ['Mengimpor Data dan Mengunduh Template Pegawai', 'pegawai.import_template', 'pegawai'],
            ['Mengekspor Data Pegawai', 'pegawai.export', 'pegawai'],
            // Unit Kerja
            ['Melihat Unit Kerja', 'unit_kerja.view', 'unit_kerja'],
            ['Menambah Unit Kerja', 'unit_kerja.create', 'unit_kerja'],
            ['Mengubah Unit Kerja', 'unit_kerja.update', 'unit_kerja'],
            // Jabatan
            ['Melihat Jabatan', 'jabatan.view', 'jabatan'],
            ['Menambah Jabatan', 'jabatan.create', 'jabatan'],
            ['Mengubah Jabatan', 'jabatan.update', 'jabatan'],
            // Pangkat
            ['Melihat Pangkat', 'pangkat.view', 'pangkat'],
            ['Menambah Pangkat', 'pangkat.create', 'pangkat'],
            ['Mengubah Pangkat', 'pangkat.update', 'pangkat'],
            // Golongan PPPK
            ['Melihat Golongan PPPK', 'pppk.view', 'pppk'],
            ['Menambah Golongan PPPK', 'pppk.create', 'pppk'],
            ['Mengubah Golongan PPPK', 'pppk.update', 'pppk'],
            // Gaji
            ['Melihat Data Gaji', 'gaji.view', 'gaji'],
            ['Menambah Data Gaji', 'gaji.create', 'gaji'],
            ['Mengubah Data Gaji', 'gaji.update', 'gaji'],
            // Laporan KGB
            ['Mengelola Laporan KGB (Melihat, Mencetak, dan Memproses)', 'laporan_kgb.manage', 'laporan_kgb'],
            // Riwayat KGB
            ['Mengelola Riwayat KGB (Melihat dan Mencetak Ulang)', 'riwayat_kgb.manage', 'riwayat_kgb'],
        ];
        DB::transaction(function () use ($permissions) {
            foreach ($permissions as [$nama, $kode, $modul]) {
                Permission::updateOrCreate(
                    ['kode' => $kode],
                    [
                        'nama' => $nama,
                        'modul' => $modul,
                        'deskripsi' => $nama . ' pada aplikasi SIGAP.',
                    ]
                );
            }
        });
    }
}

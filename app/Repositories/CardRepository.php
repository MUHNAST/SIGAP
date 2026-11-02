<?php
namespace App\Repositories;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
class CardRepository
{
    /**
     * Query dasar pegawai sesuai unit kerja user.
     */
    private function employeeQuery(?User $user = null): Builder
    {
        $query = Employee::query();
        if ($user && $user->role === 'admin') {
            $query->where( 'kode_unit_kerja', $user->kode_unit_kerja );
        }
        return $query;
    }
    /**
     * Mendapatkan data pegawai berdasarkan jenis agenda.
     *
     * @param string $type kp|kgb|mp
     */
    public function getUpcoming( string $jenis, ?User $user = null ) {
        $query = $this->employeeQuery($user)->where('status', 'Aktif');
        return match ($jenis) {
            'kp' => $query
                ->whereNotNull('tmt_pangkat_baru')
                ->whereBetween('tmt_pangkat_baru', [now(), now()->copy()->addMonths(2),])
                ->orderBy('tmt_pangkat_baru')
                ->get(),
            'kgb' => $query
                ->whereNotNull('tmt_kgb_baru')
                ->whereBetween('tmt_kgb_baru', [now(), now()->copy()->addMonths(2),])
                ->orderBy('tmt_kgb_baru')
                ->get(),
            'mp' => $query
                ->whereNotNull('tmt_pensiun')
                ->whereBetween('tmt_pensiun', [now(), now()->copy()->addMonths(6),])
                ->orderBy('tmt_pensiun')
                ->get(),
            default => collect(),
        };
    }
    /**
     * Informasi berdasarkan jenis agenda.
     */
    public function getTypeInformation(string $jenis): array
    {
        return match ($jenis) {
            'kp' => [
                'jenis' => 'kp',
                'title' => 'Kenaikan Pangkat',
                'short_title' => 'Naik Pangkat',
                'deskripsi' => 'Daftar pegawai yang akan memasuki masa kenaikan pangkat dalam 2 bulan ke depan.',
                'date_field' => 'tmt_pangkat_baru',
                'date_label' => 'TMT Kenaikan Pangkat',
                'period' => 'Dalam 2 bulan ke depan',
                'icon' => 'fa-arrow-trend-up',
                'color' => 'green',
            ],
            'kgb' => [
                'jenis' => 'kgb',
                'title' => 'Kenaikan Gaji Berkala',
                'short_title' => 'KGB',
                'deskripsi' => 'Daftar pegawai yang akan memasuki masa kenaikan gaji berkala dalam 2 bulan ke depan.',
                'date_field' => 'tmt_kgb_baru',
                'date_label' => 'TMT KGB',
                'period' => 'Dalam 2 bulan ke depan',
                'icon' => 'fa-money-bill-wave',
                'color' => 'purple',
            ],
            'mp' => [
                'jenis' => 'mp',
                'title' => 'Masa Pensiun',
                'short_title' => 'Pensiun',
                'deskripsi' => 'Daftar pegawai yang akan memasuki masa pensiun dalam 6 bulan ke depan.',
                'date_field' => 'tmt_pensiun',
                'date_label' => 'TMT Pensiun',
                'period' => 'Dalam 6 bulan ke depan',
                'icon' => 'fa-user-clock',
                'color' => 'orange',
            ],
            default => [
                'jenis' => $jenis,
                'title' => 'Data Tidak Ditemukan',
                'short_title' => 'Data',
                'deskripsi' => 'Data agenda tidak ditemukan.',
                'date_field' => null,
                'date_label' => 'Tanggal',
                'period' => null,
                'icon' => 'fa-circle-question',
                'color' => 'slate',
            ],
        };
    }
}

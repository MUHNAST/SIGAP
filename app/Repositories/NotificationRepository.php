<?php
namespace App\Repositories;

use App\Models\Employee;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
class NotificationRepository
{
    /**
     * Semua notifikasi (Super Admin)
     */
    public function getAll(array $data = ['*']): LengthAwarePaginator
    {
        return Notification::with(['employee.department'])->select($data)->latest()->get();
    }
    /**
     * Notifikasi pegawai sendiri
     */
    public function getByEmployee(string $nip,array $data = ['*']): LengthAwarePaginator {
        return Notification::with(['employee.department'])->select($data)->where('nip',$nip)->latest()->paginate(config('app.pagination'));
    }
    /**
     * Notifikasi admin berdasarkan unit kerja
     */
    public function getByUnitKerja(string $kodeUnitKerja): LengthAwarePaginator {
        return Notification::with(['employee.department'])
            ->whereHas('employee', function ($q) use ($kodeUnitKerja) {
                $q->where('kode_unit_kerja',$kodeUnitKerja);
            })->latest()->paginate(config('app.pagination'));
    }
    /**
     * Ambil notifikasi berdasarkan ID milik pegawai
     */
    public function getMyNotificationById(int $id,string $nip) {
        return Notification::where('id', $id)->where('nip',$nip)->firstOrFail();
    }
    /**
     * Ambil notifikasi ID
     */
    public function getById(int $id,array $data = ['*']) {
        return Notification::with(['employee.department'])->select($data)->findOrFail($id);
    }
    /**
     * Notifikasi terbaru
     */
    public function getLatest(string $nip = null,int $limit = 5): Collection {
        $query = Notification::with('employee.department')->latest();
        if (!is_null($nip)) {
            $query->where('nip',$nip);
        }
        return $query->limit($limit)->get();
    }
    /**
     * Notifikasi terbaru unit kerja
     */
    public function getLatestByUnitKerja(string $kodeUnitKerja,int $limit = 5): Collection {
        return Notification::with('employee.department')
            ->whereHas('employee', function ($q) use ($kodeUnitKerja) {
                $q->where('kode_unit_kerja',$kodeUnitKerja);
            })->latest()->limit($limit)->get();
    }
    /**
     * Simpan notifikasi
     */
    public function create(array $data)
    {
        return Notification::create($data);
    }
    /**
     * Tandai satu Dibaca
     */
    public function markAsRead(int $id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['sudah_dibaca' => true]);
        return $notification;
    }
    /**
     * Tandai semua Dibaca
     */
    public function markAllAsRead(string $nip = null){
        $query = Notification::query();
        if (!is_null($nip)) {
            $query->where('nip',$nip);
        }
        return $query->update(['sudah_dibaca' => true]);
    }
    /**
     * Tandai semua unit kerja sudah_dibaca
     */
    public function markAllAsReadByUnitKerja(string $kodeUnitKerja){
        return Notification::whereHas('employee',
            function ($q) use ($kodeUnitKerja) {
                $q->where('kode_unit_kerja',$kodeUnitKerja);
            }
        )
        ->update(['sudah_dibaca' => true]);
    }
    /**
     * Ambil satu notifikasi admin unit kerja
     */
    public function getByUnitKerjaNotification(int $id,string $kodeUnitKerja) {
        return Notification::with('employee')->where('id', $id)->whereHas('employee',
            function ($q) use ($kodeUnitKerja) {
                $q->where('kode_unit_kerja',$kodeUnitKerja);
            }
            )->first();
    }
    /**
     * Cek duplikasi notifikasi
     */
    public function existsNotification(string $nip,string $jenis,Carbon $referenceDate): bool {
        return Notification::where('nip',$nip)->where('jenis',$jenis)->whereDate('tanggal_referensi',$referenceDate->toDateString())->exists();
    }
    /**
     * Hitung unread
     */
    public function countUnread(string $nip = null){
        $query = Notification::where('sudah_dibaca',false);
        if (!is_null($nip)) {
            $query->where('nip',$nip);
        }
        return $query->count();
    }
    /**
     * Hitung semua
     */
    public function countAll(string $nip = null){
        $query = Notification::query();
        if (!is_null($nip)) {
            $query->where('nip',$nip);
        }
        return $query->count();
    }
    /**
     * Hitung unit kerja
     */
    public function countByUnitKerja(string $kodeUnitKerja){
        return Notification::whereHas('employee',
            function ($q) use ($kodeUnitKerja) {
                $q->where('kode_unit_kerja',$kodeUnitKerja);
            }
        )->count();
    }
    /**
     * Hitung unread unit kerja
     */
    public function countUnreadByUnitKerja(string $kodeUnitKerja){
        return Notification::whereHas('employee',
            function ($q) use ($kodeUnitKerja) {
                $q->where('kode_unit_kerja',$kodeUnitKerja);
            }
        )->where('sudah_dibaca',false)->count();
    }
    /**
     * Filter semua
     */
    public function getFiltered(array $filters = [],?string $nip = null): LengthAwarePaginator {
        $query = Notification::with(['employee.department'])->latest();
        if (!is_null($nip)) {
            $query->where('nip',$nip);
        }
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('judul','LIKE',"%{$search}%")
                ->orWhere('pesan','LIKE',"%{$search}%");
            });
        }
        if (!empty($filters['kategori'])) {
            $query->where('jenis',$filters['kategori']);
        }
        if (isset($filters['sudah_dibaca']) &&$filters['sudah_dibaca'] !== '') {
            $query->where('sudah_dibaca',(bool)$filters['sudah_dibaca']);
        }
        return $query->paginate(config('app.pagination'))->withQueryString();
    }
    /**
     * Filter unit kerja admin
     */
    public function getFilteredByUnitKerja(array $filters,string $kodeUnitKerja): LengthAwarePaginator {
        $query = Notification::with(['employee.department'])->whereHas('employee',
                function ($q) use ($kodeUnitKerja) {
                    $q->where('kode_unit_kerja',$kodeUnitKerja);
                })->latest();
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('judul','LIKE',"%{$search}%")
                ->orWhere('pesan','LIKE',"%{$search}%");
            });
        }
        if (!empty($filters['kategori'])) {
            $query->where('jenis',$filters['kategori']);
        }
        if ( isset($filters['sudah_dibaca']) && $filters['sudah_dibaca'] !== '') {
            $query->where('sudah_dibaca',(bool)$filters['sudah_dibaca']);
        }
        return $query->paginate(config('app.pagination'))->withQueryString();
    }
    public function getNextPangkat(): ?Notification
    {
        return Notification::with('employee.department')
            ->where('jenis', 'Pangkat')
            ->where('tanggal_referensi', '>=', now()->toDateString())
            ->orderBy('tanggal_referensi')
            ->first();
    }

    public function getNextKGB(): ?Notification
    {
        return Notification::with('employee.department')
            ->where('jenis', 'KGB')
            ->where('tanggal_referensi', '>=', now()->toDateString())
            ->orderBy('tanggal_referensi')
            ->first();
    }

    public function getNextRetirement(): ?Notification
    {
        return Notification::with('employee.department')
            ->where('jenis', 'Pensiun')
            ->where('tanggal_referensi', '>=', now()->toDateString())
            ->orderBy('tanggal_referensi')
            ->first();
    }
    public function getNextPangkatByUnitKerja(string $kodeUnitKerja): ?Notification
    {
        return Notification::with('employee.department')
            ->where('jenis', 'Pangkat')
            ->where('tanggal_referensi', '>=', now()->toDateString())
            ->whereHas('employee', function ($q) use ($kodeUnitKerja) {
                $q->where('kode_unit_kerja', $kodeUnitKerja);
            })
            ->orderBy('tanggal_referensi')
            ->first();
    }

    public function getNextKGBByUnitKerja(string $kodeUnitKerja): ?Notification
    {
        return Notification::with('employee.department')
            ->where('jenis', 'KGB')
            ->where('tanggal_referensi', '>=', now()->toDateString())
            ->whereHas('employee', function ($q) use ($kodeUnitKerja) {
                $q->where('kode_unit_kerja', $kodeUnitKerja);
            })
            ->orderBy('tanggal_referensi')
            ->first();
    }

    public function getNextRetirementByUnitKerja(string $kodeUnitKerja): ?Notification
    {
        return Notification::with('employee.department')
            ->where('jenis', 'Pensiun')
            ->where('tanggal_referensi', '>=', now()->toDateString())
            ->whereHas('employee', function ($q) use ($kodeUnitKerja) {
                $q->where('kode_unit_kerja', $kodeUnitKerja);
            })
            ->orderBy('tanggal_referensi')
            ->first();
    }
    /**
     * Cari notifikasi berdasarkan:
     * - NIP pegawai
     * - jenis notifikasi
     * - reference date
     */
    public function findNotification( string $nip, string $jenis, string $referenceDate): ?Notification {
        return Notification::query()->where('nip', $nip)->where('jenis', $jenis)->whereDate('tanggal_referensi', $referenceDate)->first();
    }
    public function getByNip(string $nip)
    {
        return Notification::with('employee')
            ->where('nip', $nip)
            ->latest()
            ->get();
    }

    public function countByNip(string $nip): int
    {
        return Notification::where('nip', $nip)->count();
    }

    public function countUnreadByNip(string $nip): int
    {
        return Notification::where('nip', $nip)
            ->where('sudah_dibaca', false)
            ->count();
    }

    public function getByNipNotification(int $id, string $nip)
    {
        return Notification::where('id', $id)
            ->where('nip', $nip)
            ->first();
    }

    public function markAllAsReadByNip(string $nip): int
    {
        return Notification::where('nip', $nip)
            ->where('sudah_dibaca', false)
            ->update([
                'sudah_dibaca' => true,
            ]);
    }

    public function getFilteredByNip(array $filters, string $nip)
    {
        $query = Notification::with('employee')
            ->where('nip', $nip);

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('pesan', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['kategori'])) {
            $query->where('jenis', $filters['kategori']);
        }

        if (
            isset($filters['sudah_dibaca']) &&
            $filters['sudah_dibaca'] !== ''
        ) {
            $query->where(
                'sudah_dibaca',
                $filters['sudah_dibaca']
            );
        }

        return $query->latest()->paginate(10)->withQueryString();
    }

    public function getLatestByNip(string $nip, int $limit = 5)
    {
        return Notification::with('employee')
            ->where('nip', $nip)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getNextPangkatByNip(string $nip)
    {
        return Employee::where('nip', $nip)
            ->whereNotNull('tmt_pangkat_baru')
            ->value('tmt_pangkat_baru');
    }

    public function getNextKGBByNip(string $nip)
    {
        return Employee::where('nip', $nip)
            ->whereNotNull('tmt_kgb_baru')
            ->value('tmt_kgb_baru');
    }

    public function getNextRetirementByNip(string $nip)
    {
        return Employee::where('nip', $nip)
            ->whereNotNull('tmt_pensiun')
            ->value('tmt_pensiun');
    }
}

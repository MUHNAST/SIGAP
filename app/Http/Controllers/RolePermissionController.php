<?php
namespace App\Http\Controllers;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class RolePermissionController extends Controller
{
    /**
     * Kode izin lama yang sudah digabung atau tidak digunakan lagi.
     * Tidak ditampilkan sebagai pilihan terpisah.
     */
    private array $legacyPermissionCodes = [
        'pegawai.import',
        'pegawai.template',
        'laporan_kgb.view',
        'laporan_kgb.print',
        'laporan_kgb.process',
        'riwayat_kgb.view',
        'riwayat_kgb.print',
    ];
    /**
     * Memastikan hanya Super Admin yang dapat mengatur hak akses.
     */
    private function authorizeSuperAdmin(): void
    {
        abort_unless(
            Auth::check() && Auth::user()->role === 'super_admin',
            403,
            'Anda tidak memiliki izin untuk mengakses Manajemen Hak Akses.'
        );
    }
    /**
     * Menampilkan izin operasional yang dapat diatur untuk Admin.
     */
    public function index()
    {
        $this->authorizeSuperAdmin();
        $title = 'Manajemen Hak Akses';
        $sub_title = 'Atur akses menu dan fitur bagi pengguna Admin';
        $user = Auth::user();
        $hiddenCodes = $this->legacyPermissionCodes;
        // Ambil izin yang dapat dikonfigurasi.
        $permissions = Permission::query()
            ->whereNotIn('kode', $hiddenCodes)
            ->where('modul', '!=', 'hak_akses')
            ->orderBy('modul')
            ->orderBy('nama')
            ->get()
            ->groupBy('modul');
        // Ambil izin yang saat ini dimiliki role Admin.
        $selectedPermissionIds = RolePermission::query()
            ->where('role', 'admin')
            ->whereHas('permission', function ($query) use ($hiddenCodes) {
                $query->whereNotIn('kode', $hiddenCodes)
                    ->where('modul', '!=', 'hak_akses');
            })
            ->pluck('permission_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        return view(
            'setting.role-permission.index',
            compact(
                'permissions',
                'selectedPermissionIds',
                'title',
                'sub_title',
                'user'
            )
        );
    }
    /**
     * Menyimpan izin operasional Admin sesuai pilihan Super Admin.
     */
    public function update(Request $request)
    {
        $this->authorizeSuperAdmin();
        $validated = $request->validate([
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:permissions,id',
            ],
        ], [
            'permission_ids.array' => 'Format daftar hak akses tidak valid.',
            'permission_ids.*.required' => 'ID hak akses wajib diisi.',
            'permission_ids.*.integer' => 'ID hak akses tidak valid.',
            'permission_ids.*.distinct' => 'Terdapat hak akses yang dipilih lebih dari sekali.',
            'permission_ids.*.exists' => 'Hak akses yang dipilih tidak ditemukan.',
        ]);
        $permissionIds = array_map(
            'intval',
            $validated['permission_ids'] ?? []
        );
        $hiddenCodes = $this->legacyPermissionCodes;
        /*
         * Pastikan seluruh izin yang dikirim memang termasuk
         * izin yang ditampilkan dan boleh dikonfigurasi.
         *
         * Mencegah manipulasi request untuk memasukkan:
         * - Izin lama atau izin yang sudah digabung.
         * - Izin modul Manajemen Hak Akses.
         */
        $validPermissionCount = Permission::query()
            ->whereIn('id', $permissionIds)
            ->whereNotIn('kode', $hiddenCodes)
            ->where('modul', '!=', 'hak_akses')
            ->count();
        if ($validPermissionCount !== count($permissionIds)) {
            throw ValidationException::withMessages([
                'permission_ids' => 'Terdapat hak akses yang tidak dapat dikonfigurasi.',
            ]);
        }
        DB::transaction(function () use ($permissionIds) {
            // Hapus konfigurasi izin Admin sebelumnya.
            RolePermission::query()
                ->where('role', 'admin')
                ->delete();
            // Simpan izin sesuai pilihan Super Admin.
            $now = now();
            $rows = collect($permissionIds)
                ->map(fn ($permissionId) => [
                    'role' => 'admin',
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();
            if (!empty($rows)) {
                RolePermission::query()->insert($rows);
            }
        });
        return redirect()
            ->route('setting.role-permissions.index')
            ->with('success', 'Hak akses Admin berhasil diperbarui.');
    }
}

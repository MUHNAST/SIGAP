<?php
namespace App\Services;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
class EmployeeUserSyncService
{
    public function sync(Employee $employee, ?string $oldNip = null): User
    {
        Log::info('EmployeeUserSyncService: memulai sinkronisasi akun.', [
            'employee_nip' => $employee->nip,
            'old_nip' => $oldNip,
            'employee_email' => $employee->email,
            'employee_no_hp' => $employee->no_hp,
        ]);
        /*
         * 1. Cari akun berdasarkan NIP lama.
         */
        $user = null;
        if ($oldNip) {
            $user = User::where('nip', $oldNip)->first();
        }
        /*
         * 2. Jika belum ditemukan, cari berdasarkan NIP saat ini.
         */
        if (!$user) {
            $user = User::where('nip', $employee->nip)->first();
        }
        /*
         * 3. Jika belum ditemukan, cari berdasarkan username.
         */
        if (!$user) {
            $user = User::where('username', $employee->nip)->first();
        }
        Log::info('EmployeeUserSyncService: hasil pencarian akun.', [
            'employee_nip' => $employee->nip,
            'user_found' => $user !== null,
            'user_id' => $user?->id,
            'user_nip' => $user?->nip,
            'user_username' => $user?->username,
            'user_role' => $user?->role,
        ]);
        /*
         * 4. Buat akun baru jika NIP dan username tidak ditemukan.
         */
        if (!$user) {
            $user = new User();
            $user->role = 'user';
            $user->password = Hash::make('123456789');
            Log::info('EmployeeUserSyncService: membuat akun baru.', [
                'employee_nip' => $employee->nip,
            ]);
        } else {
            Log::info('EmployeeUserSyncService: menggunakan akun yang sudah tersedia.', [
                'employee_nip' => $employee->nip,
                'user_id' => $user->id,
                'user_role' => $user->role,
            ]);
        }
        /*
         * 5. Siapkan email dan nomor HP.
         */
        $email = $employee->email ?: null;
        $noHp = $employee->no_hp ?: null;
        /*
         * 6. Periksa duplikasi email.
         *
         * Duplikasi hanya diperbolehkan antara role user
         * dan super_admin.
         */
        if ($email) {
            $emailOwner = $this->findConflictingUser(
                'email',
                $email,
                $user
            );
            if ($emailOwner) {
                Log::warning(
                    'EmployeeUserSyncService: email digunakan akun lain, dikosongkan.',
                    [
                        'employee_nip' => $employee->nip,
                        'email' => $email,
                        'user_id' => $user->id,
                        'conflicting_user_id' => $emailOwner->id,
                        'conflicting_role' => $emailOwner->role,
                    ]
                );
                $email = null;
            }
        }
        /*
         * 7. Periksa duplikasi nomor HP.
         */
        if ($noHp) {
            $phoneOwner = $this->findConflictingUser(
                'no_hp',
                $noHp,
                $user
            );
            if ($phoneOwner) {
                Log::warning(
                    'EmployeeUserSyncService: nomor HP digunakan akun lain, dikosongkan.',
                    [
                        'employee_nip' => $employee->nip,
                        'no_hp' => $noHp,
                        'user_id' => $user->id,
                        'conflicting_user_id' => $phoneOwner->id,
                        'conflicting_role' => $phoneOwner->role,
                    ]
                );
                $noHp = null;
            }
        }
        /*
         * 8. Sinkronisasi data akun.
         *
         * Role dan password akun lama tidak diubah.
         */
        $user->nip = $employee->nip;
        $user->username = $employee->nip;
        $user->nama = $employee->nama;
        $user->email = $email;
        $user->no_hp = $noHp;
        $user->kode_unit_kerja = $employee->kode_unit_kerja;
        $user->foto = $employee->foto ?: 'images/default-user.png';
        $user->save();
        Log::info('EmployeeUserSyncService: akun berhasil disinkronkan.', [
            'employee_nip' => $employee->nip,
            'user_id' => $user->id,
            'user_role' => $user->role,
            'username' => $user->username,
            'email' => $user->email,
            'no_hp' => $user->no_hp,
        ]);
        return $user;
    }
    /**
     * Mencari akun yang menyebabkan konflik email atau nomor HP.
     *
     * Duplikasi yang diperbolehkan:
     * - user dengan super_admin
     *
     * Duplikasi yang tidak diperbolehkan:
     * - user dengan user
     * - super_admin dengan super_admin
     * - admin dengan admin
     * - admin dengan user
     * - admin dengan super_admin
     */
    private function findConflictingUser(
        string $column,
        string $value,
        User $user
    ): ?User {
        $allowedColumns = ['email', 'no_hp'];
        if (!in_array($column, $allowedColumns, true)) {
            throw new \InvalidArgumentException(
                'Kolom pemeriksaan duplikasi tidak valid.'
            );
        }
        $query = User::query()
            ->where($column, $value);
        if ($user->exists) {
            $query->where('id', '!=', $user->id);
        }
        $allowedOtherRole = match ($user->role) {
            'user' => 'super_admin',
            'super_admin' => 'user',
            default => null,
        };
        if ($allowedOtherRole !== null) {
            $query->where('role', '!=', $allowedOtherRole);
        }
        return $query->first();
    }
}

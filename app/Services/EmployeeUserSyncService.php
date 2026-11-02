<?php
namespace App\Services;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class EmployeeUserSyncService
{
    public function sync(Employee $employee, ?string $oldNip = null): User
    {
        // Cari akun menggunakan NIP lama jika NIP pegawai berubah.
        $user = User::query()
            ->where('nip', $oldNip ?? $employee->nip)
            ->first();
        // Jika akun berdasarkan NIP lama tidak ditemukan,
        // coba cari menggunakan NIP terbaru.
        if (!$user && $oldNip !== null) {
            $user = User::query()
                ->where('nip', $employee->nip)
                ->first();
        }
        // Validasi email.
        if (filled($employee->email)) {
            $emailQuery = User::query()
                ->where('email', $employee->email);
            if ($user) {
                $emailQuery->where('id', '!=', $user->id);
            }
            if ($emailQuery->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Email pegawai sudah digunakan oleh akun pengguna lain.',
                ]);
            }
        }
        // Validasi nomor HP.
        if (filled($employee->no_hp)) {
            $phoneQuery = User::query()
                ->where('no_hp', $employee->no_hp);
            if ($user) {
                $phoneQuery->where('id', '!=', $user->id);
            }
            if ($phoneQuery->exists()) {
                throw ValidationException::withMessages([
                    'no_hp' => 'Nomor HP pegawai sudah digunakan oleh akun pengguna lain.',
                ]);
            }
        }
        $isNewUser = !$user;
        // Buat akun baru jika belum tersedia.
        if (!$user) {
            $user = new User();
            $user->role = 'user';
            $user->password = Hash::make(Str::random(64));
        }
        // Sinkronisasi identitas pegawai.
        $user->nip = $employee->nip;
        $user->username = $employee->nip;
        $user->nama = $employee->nama;
        $user->email = $employee->email ?: null;
        $user->no_hp = $employee->no_hp ?: null;
        $user->kode_unit_kerja = $employee->kode_unit_kerja;
        $user->foto = $employee->foto;
        // Role akun lama tidak diubah.
        if ($isNewUser) {
            $user->role = 'user';
        }
        $user->save();
        return $user;
    }
}

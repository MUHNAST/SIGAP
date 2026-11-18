<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        $user = $this->user();
        $role = $user->role;
        /*
         * ATURAN EMAIL
         *
         * Super Admin:
         * Boleh sama dengan User.
         * Tidak boleh sama dengan Admin dan Super Admin lain.
         *
         * User:
         * Boleh sama dengan Super Admin.
         * Tidak boleh sama dengan Admin dan User lain.
         *
         * Admin:
         * Tidak boleh sama dengan semua role.
         */
        $emailRules = [
            'required',
            'email',
            'max:255',
        ];
        if ($role === 'super_admin') {
            $emailRules[] = Rule::unique('users', 'email')->whereIn('role', ['super_admin', 'admin'])->ignore($user->id);
        } elseif ($role === 'user') {
            $emailRules[] = Rule::unique('users', 'email')->whereIn('role', ['admin', 'user'])->ignore($user->id);
        } else {
            $emailRules[] = Rule::unique('users', 'email')->ignore($user->id);
        }
        /*
         * ATURAN NOMOR HP
         */
        $noHpRules = [ 'required', 'regex:/^08[0-9]{8,12}$/', ];
        if ($role === 'super_admin') {
            $noHpRules[] = Rule::unique('users', 'no_hp')->whereIn('role', ['super_admin', 'admin'])->ignore($user->id);
        } elseif ($role === 'user') {
            $noHpRules[] = Rule::unique('users', 'no_hp')->whereIn('role', ['admin', 'user'])->ignore($user->id);
        } else {
            $noHpRules[] = Rule::unique('users', 'no_hp')->ignore($user->id);
        }
        return [
            'nama' => [ 'required', 'string', 'min:8', 'max:100'],
            'email' => $emailRules,
            'no_hp' => $noHpRules,
            'foto' => [ 'nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            // Username dan status tidak dapat diubah melalui profil.
        ];
    }
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama wajib diisi.',
            'nama.string' => 'Nama harus berupa teks.',
            'nama.min' => 'Nama minimal 8 karakter.',
            'nama.max' => 'Nama maksimal 100 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'email.unique' => 'Email sudah digunakan oleh akun dengan role yang tidak diperbolehkan.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.regex' => 'Nomor HP harus diawali 08 dan terdiri dari 10-14 digit.',
            'no_hp.unique' => 'Nomor HP sudah digunakan oleh akun dengan role yang tidak diperbolehkan.',
            'foto.image' => 'File harus berupa gambar.',
            'foto.mimes' => 'Foto harus berformat JPG, JPEG, atau PNG.',
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
        ];
    }
    /**
     * Normalize input before validation.
     */
    protected function prepareForValidation(): void
    {
        $nama = $this->input('nama');
        $nama = $nama ? preg_replace('/\s+/', ' ', trim($nama)) : null;
        $this->merge([
            'nama' => $nama ? Str::title(Str::lower($nama)) : null,
        ]);
    }
}

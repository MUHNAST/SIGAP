<?php
namespace App\Http\Requests;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Mengambil pengguna dari route ketika proses update.
        $routeUser = $this->route('user');
        if ($routeUser instanceof User) {
            $existingUser = $routeUser;
        } else {
            $routeId = $this->route('id') ?? $routeUser;
            $existingUser = is_numeric($routeId) ? User::find($routeId) : null;
        }
        // ID akun yang sedang diedit.
        $userId = $existingUser?->id ?? $this->route('id') ?? $routeUser;
        // Prioritaskan role dari database ketika update.
        // Jika create, gunakan role dari input atau default Admin.
        $role = $existingUser?->role
            ?? ($this->isMethod('post')
                ? 'admin'
                : $this->input('role', 'admin'));
        // Aturan dasar email.
        $emailRules = [
            'required',
            'email',
            'max:255',
        ];
        // Aturan dasar nomor HP.
        $noHpRules = [
            'required',
            'regex:/^08[0-9]{8,12}$/',
        ];
        // Aturan dasar unit kerja.
        $unitRules = [
            'required',
            'exists:departments,kode',
        ];
        /*
         * ATURAN EMAIL DAN NOMOR HP
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
        if ($role === 'super_admin') {
            $emailRules[] = Rule::unique('users', 'email')
                ->whereIn('role', ['super_admin', 'admin'])
                ->ignore($userId);
            $noHpRules[] = Rule::unique('users', 'no_hp')
                ->whereIn('role', ['super_admin', 'admin'])
                ->ignore($userId);
        } elseif ($role === 'user') {
            $emailRules[] = Rule::unique('users', 'email')
                ->whereIn('role', ['admin', 'user'])
                ->ignore($userId);
            $noHpRules[] = Rule::unique('users', 'no_hp')
                ->whereIn('role', ['admin', 'user'])
                ->ignore($userId);
        } else {
            $emailRules[] = Rule::unique('users', 'email')
                ->ignore($userId);
            $noHpRules[] = Rule::unique('users', 'no_hp')
                ->ignore($userId);
        }
        /*
         * ATURAN UNIT KERJA
         *
         * Admin dan Super Admin tidak boleh menggunakan
         * unit kerja yang sama.
         *
         * User bebas menggunakan unit kerja yang sama.
         */
        if (in_array($role, ['super_admin', 'admin'], true)) {
            $unitRules[] = Rule::unique('users', 'kode_unit_kerja')
                ->whereIn('role', ['super_admin', 'admin'])
                ->ignore($userId);
        }
        return [
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'nama' => [
                'required',
                'string',
                'max:100',
            ],
            'email' => $emailRules,
            'no_hp' => $noHpRules,
            'kode_unit_kerja' => $unitRules,
            'foto' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'username.required' => 'Username wajib diisi.',
            'username.max' => 'Username maksimal 50 karakter.',
            'username.unique' => 'Username sudah digunakan.',
            'nama.required' => 'Nama wajib diisi.',
            'nama.max' => 'Nama maksimal 100 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'email.unique' => 'Email yang sama hanya boleh digunakan oleh role Super Admin dengan User.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.regex' => 'Nomor HP harus diawali 08 dan terdiri dari 10-14 digit.',
            'no_hp.unique' => 'Email yang sama hanya boleh digunakan oleh role Super Admin dengan User.',
            'kode_unit_kerja.required' => 'Unit Kerja wajib dipilih.',
            'kode_unit_kerja.exists' => 'Unit Kerja tidak ditemukan.',
            'kode_unit_kerja.unique' => 'Unit kerja sudah digunakan oleh akun Admin atau Super Admin.',
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
        $nama = $this->nama
            ? preg_replace('/\s+/', ' ', trim($this->nama))
            : null;
        $this->merge([
            'nama' => $nama ? Str::title(Str::lower($nama)) : null,
        ]);
    }
}

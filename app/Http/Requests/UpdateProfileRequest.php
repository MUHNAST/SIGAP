<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        $user = $this->user();
        return [
            'nama' => ['required','string','min:8','max:100'],
            // 'username' => ['required','string','min:4','max:30',Rule::unique('users', 'username')->ignore($user->id),],
            'email' => ['required','email','max:100',Rule::unique('users', 'email')->ignore($user->id),],
            'no_hp' => ['required','regex:/^08[0-9]{8,13}$/',Rule::unique('users', 'no_hp')->ignore($user->id),],
        ];
    }
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama ma wajib diisi.',
            'nama.unique' => 'Nama sudah digunakan.',
            'nama.min' => 'Nama minimal 8 karakter.',
            'nama.max' => 'Nama maksimal 100  karakter.',
            // 'username.required' => 'Username wajib diisi.',
            // 'username.unique' => 'Username sudah digunakan.',
            // 'username.min' => 'Username minimal 4 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.regex' => 'Nomor HP harus diawali 08 dan terdiri dari 10-15 digit.',
            'no_hp.unique' => 'Nomor HP sudah digunakan.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AuthRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
        // dd('koko');
        return [
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'remember' => ['sometimes', 'accepted'],
        ];
    }

    public function messages(): array
    {
        // dd('lppl');
        return [
            'username.required' => 'Username wajib diisi.',
            'username.string'   => 'Username harus berupa teks.',
            'username.max'      => 'Username maksimal 50 karakter.',

            'password.required' => 'Password wajib diisi.',
            'password.string'   => 'Password harus berupa teks.',
            'password.min'      => 'Password minimal 8 karakter.',
            'password.max'      => 'Password maksimal 255 karakter.',

            'remember.accepted' => 'Pilihan Ingat Saya tidak valid.',
        ];
    }
    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => trim($this->username),
        ]);
    }
}

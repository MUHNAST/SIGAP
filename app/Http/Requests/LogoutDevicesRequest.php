<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LogoutDevicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required'],
            'active_tab' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Password harus diisi.',
        ];
    }
}

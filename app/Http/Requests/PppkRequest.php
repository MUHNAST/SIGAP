<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class PppkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        $pppk = $this->route('pppk')?->kode;
        return [
            'kode' => ['required','string','max:8',Rule::unique('pppk', 'kode')->ignore($pppk, 'kode')],
            'nama' => ['required','string','max:255',Rule::unique('pppk', 'nama')->ignore($pppk, 'kode')],
            'urutan_golongan' => ['required','integer','min:1','max:255',Rule::unique('pppk', 'urutan_golongan')->ignore($pppk, 'kode'),],
        ];
    }
    public function messages(): array
    {
        return [
            'kode.required' => 'Kode golongan wajib diisi.',
            'kode.string'   => 'Kode golongan harus berupa teks.',
            'kode.max'      => 'Kode golongan maksimal 8 karakter.',
            'kode.unique'   => 'Kode golongan sudah digunakan.',
            'nama.required' => 'Nama Golongan wajib diisi.',
            'nama.string'   => 'Nama Golongan harus berupa teks.',
            'nama.max'      => 'Nama Golongan maksimal 255 karakter.',
            'nama.unique'   => 'Nama Golongan sudah digunakan.',
            'urutan_golongan.required' => 'Urutan Golongan wajib diisi.',
            'urutan_golongan.integer'  => 'Urutan Golongan harus berupa angka.',
            'urutan_golongan.min'      => 'Urutan Golongan minimal 1.',
            'urutan_golongan.max'      => 'Urutan Golongan maksimal 255.',
            'urutan_golongan.unique'   => 'Urutan Golongan sudah digunakan.',
        ];
    }
    protected function prepareForValidation(): void
    {
        $this->merge([
            'kode' => trim($this->kode),
            'nama' => trim($this->nama),
        ]);
    }
}

<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class RankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        $rank = $this->route('rank')?->id;
        return [
            'kode' => ['required','string','max:8',Rule::unique('ranks', 'kode')->ignore($rank)],
            'nama' => ['required','string','max:255',Rule::unique('ranks', 'nama')->ignore($rank)],
            'urutan_pangkat' => ['required','integer','min:1','max:255',Rule::unique('ranks', 'urutan_pangkat')->ignore($rank),],
        ];
    }
    public function messages(): array
    {
        return [
            'kode.required' => 'Kode golongan wajib diisi.',
            'kode.string'   => 'Kode golongan harus berupa teks.',
            'kode.max'      => 'Kode golongan maksimal 8 karakter.',
            'kode.unique'   => 'Kode golongan sudah digunakan.',
            'nama.required' => 'Nama pangkat wajib diisi.',
            'nama.string'   => 'Nama pangkat harus berupa teks.',
            'nama.max'      => 'Nama pangkat maksimal 255 karakter.',
            'nama.unique'   => 'Nama pangkat sudah digunakan.',
            'urutan_pangkat.required' => 'Urutan pangkat wajib diisi.',
            'urutan_pangkat.integer'  => 'Urutan pangkat harus berupa angka.',
            'urutan_pangkat.min'      => 'Urutan pangkat minimal 1.',
            'urutan_pangkat.max'      => 'Urutan pangkat maksimal 255.',
            'urutan_pangkat.unique'   => 'Urutan pangkat sudah digunakan.',
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

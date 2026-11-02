<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
class DepartmentRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $department = $this->route('department');
        return [
            'kode' => ['required','string','max:8',Rule::unique('departments', 'kode')->ignore($department?->kode, 'kode')],
            'nama' => ['required','string','max:100',Rule::unique('departments', 'nama')->ignore($department?->kode, 'kode'),],
            'deskripsi' => ['nullable','string','max:500',],
            'status' => ['required', 'boolean'],
        ];
    }
    public function messages(): array
    {
        return [
            'kode.required'     => 'Kode unit kerja wajib diisi.',
            'kode.string'       => 'Kode unit kerja harus berupa teks.',
            'kode.max'          => 'Kode unit kerja maksimal 8 karakter.',
            'kode.unique'       => 'Kode unit kerja sudah digunakan.',
            'nama.required'     => 'Nama unit kerja wajib diisi.',
            'nama.string'       => 'Nama unit kerja harus berupa teks.',
            'nama.max'          => 'Nama unit kerja maksimal 100 karakter.',
            'nama.unique'       => 'Nama unit kerja sudah digunakan.',
            'deskripsi.string'=> 'Deskripsi harus berupa teks.',
            'deskripsi.max'   => 'Deskripsi maksimal 500 karakter.',
            'status.required'   => 'Status wajib dipilih.',
            'status.boolean'    => 'Status tidak valid.',
        ];
    }
    protected function prepareForValidation(): void
    {
        $nama = $this->nama ? preg_replace('/\s+/', ' ', trim($this->nama)) : null;
        $this->merge([
            'nama' => $nama ? Str::title(Str::lower($nama)) : null,
            'deskripsi' => $this->deskripsi ? preg_replace('/\s+/', ' ', trim($this->deskripsi)) : null,
        ]);
    }
}

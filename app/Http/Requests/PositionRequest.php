<?php
namespace App\Http\Requests;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
class PositionRequest extends FormRequest
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
        // $position = $this->route('position');
        $position = $this->route('position');
        $kode = $position?->kode;
        return [
            'kode' => ['required','string','max:20',Rule::unique('positions', 'kode')->ignore($kode, 'kode')],
            'nama' => [ 'required', 'string', 'max:50', Rule::unique('positions', 'nama')->ignore($kode, 'kode'),],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'usia_pensiun' => ['required','integer','min:58','max:70'],
        ];
    }
    public function messages(): array
    {
        return [
            'kode.required' => 'Kode jabatan wajib diisi.',
            'kode.string'   => 'Kode jabatan harus berupa teks.',
            'kode.unique'   => 'Kode jabatan harus Unik atau beda dengan yang lain.',
            'kode.max'      => 'Kode jabatan maksimal 20 karakter.',
            'nama.required' => 'Nama jabatan wajib diisi.',
            'nama.unique'   => 'Nama jabatan harus Unik atau beda dengan yang lain.',
            'nama.string'   => 'Nama jabatan harus berupa teks.',
            'nama.max'      => 'Nama jabatan maksimal 50 karakter.',
            'deskripsi.string' => 'Deskripsi harus berupa teks.',
            'deskripsi.max'    => 'Deskripsi maksimal 255 karakter.',
            'usia_pensiun.required' => 'Masa Pensiun wajib diisi.',
            'usia_pensiun.integer' => 'Masa Pensiun harus berupa angka.',
            'usia_pensiun.max'    => 'Masa Pensiun maksimal 70 Tahun.',
            'usia_pensiun.min'    => 'Masa Pensiun Minimal 58 Tahun.',
        ];
    }
    protected function prepareForValidation(): void
    {
        $nama = $this->nama ? preg_replace('/\s+/', ' ', trim($this->nama)) : null;
        $this->merge([
            'kode' => trim((string) $this->kode),
            'nama' => $nama ? Str::title(Str::lower($nama)) : null,
            'deskripsi' => $this->deskripsi ? preg_replace('/\s+/', ' ', trim($this->deskripsi)) : null,
        ]);
    }
}

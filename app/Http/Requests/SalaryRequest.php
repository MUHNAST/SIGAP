<?php
namespace App\Http\Requests;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class SalaryRequest extends FormRequest
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
        return [
            'id_pangkat' => ['required','exists:ranks,id'],
            'tahun_masa_kerja' => ['required','integer','min:0', 'max:33', Rule::unique('salaries')->where(fn ($query) => $query->where('id_pangkat', $this->id_pangkat))->ignore($this->route('salary'))],
            'gaji_pokok' => ['required','numeric','min:0', 'max:100000000',],
        ];
    }
    public function messages(): array
    {
        return [
            'id_pangkat.required' => 'Pangkat wajib diisi.',
            'id_pangkat.exists'   => 'Pangkat tidak valid.',
            'tahun_masa_kerja.required' => 'Tahun Masa kerja wajib diisi.',
            'tahun_masa_kerja.integer'  => 'Tahun Masa kerja harus angka.',
            'tahun_masa_kerja.min'      => 'Tahun Masa kerja tidak boleh kurang dari 0.',
            'tahun_masa_kerja.max'      => 'Tahun Masa kerja tidak boleh lebih dari 33.',
            'tahun_masa_kerja.unique'   => 'Tahun Masa kerja untuk pangkat tersebut sudah ada.',
            'gaji_pokok.required' => 'Gaji pokok wajib diisi.',
            'gaji_pokok.numeric'  => 'Gaji pokok harus angka.',
            'gaji_pokok.min'      => 'Gaji pokok tidak boleh kurang dari 0.',
            'gaji_pokok.max' => 'Gaji pokok tidak boleh lebih dari Rp100.000.000.',
        ];
    }
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tahun_masa_kerja' => $this->filled('tahun_masa_kerja') ? (int) $this->tahun_masa_kerja : null,
            'gaji_pokok' => $this->filled('gaji_pokok')
                ? str_replace('.', '', (string) $this->gaji_pokok)
                : null,
        ]);
    }
}

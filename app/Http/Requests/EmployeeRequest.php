<?php
namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        $employee = $this->route('employee');
        $employeeNip = $employee instanceof Employee ? $employee->nip : $employee;
//         dd([
//     'employee' => $employee,
//     'tipe_employee' => get_debug_type($employee),
//     'employee_nip' => $employeeNip,
//     'semua_input' => $this->all(),
//     'file_dokumen' => $this->file('dokumen'),
// ]);
        return [
            /*
            |--------------------------------------------------------------------------
            | IDENTITAS PEGAWAI
            |--------------------------------------------------------------------------
            */
            'nip' => [
                'required',
                'string',
                'digits:18',
                Rule::unique('employees', 'nip')
                    ->ignore($employeeNip, 'nip'),
            ],
            'nik' => [
                // 'required',
                'nullable',
                'string',
                'digits:16',
                Rule::unique('employees', 'nik')
                    ->ignore($employeeNip, 'nip'),
            ],
            'nama' => [
                // 'required',
                'nullable',
                'string',
                'max:100',
            ],
            'email' => [
                'nullable',
                'email',
                'max:100',
                Rule::unique('employees', 'email')
                    ->ignore($employeeNip, 'nip'),
            ],
            'no_hp' => [
                // 'required',
                'nullable',
                'string',
                'regex:/^08[0-9]{8,14}$/',
                Rule::unique('employees', 'no_hp')
                    ->ignore($employeeNip, 'nip'),
            ],
            /*
            |--------------------------------------------------------------------------
            | KEPEGAWAIAN
            |--------------------------------------------------------------------------
            */
            'status_pegawai' => [
                // 'required',
                'nullable',
                Rule::in(['PNS', 'PPPK']),
            ],
            'id_pangkat' => [
                'exclude_unless:status_pegawai,PNS',
                'nullable',
                'exists:ranks,id',
            ],
            'kode_pppk' => [
                'exclude_unless:status_pegawai,PPPK',
                'nullable',
                'exists:Pppk,kode',
            ],
            'tingkat_pendidikan' => [
                // 'required',
                'nullable',
                'exists:education_levels,kode',
            ],
            'kode_unit_kerja' => [
                // 'required',
                'nullable',
                'exists:departments,kode',
            ],
            'kode_jabatan' => [
                // 'required',
                'nullable',
                'exists:positions,kode',
            ],
            /*
            |--------------------------------------------------------------------------
            | DATA PRIBADI
            |--------------------------------------------------------------------------
            */
            'tempat_lahir' => [
                // 'required',
                'nullable',
                'exists:cities,id',
            ],
            'tanggal_lahir' => [
                // 'required',
                'nullable',
                'date',
                'before:today',
            ],
            'jenis_kelamin' => [
                // 'required',
                'nullable',
                Rule::in(['Laki-laki', 'Perempuan']),
            ],
            'agama' => [
                'nullable',
                Rule::in([
                    'Islam',
                    'Kristen Protestan',
                    'Kristen Katolik',
                    'Budha',
                    'Hindu',
                    'Konghucu',
                ]),
            ],
            'alamat' => [
                'nullable',
                'string',
            ],
            'status_nikah' => [
                'nullable',
                Rule::in([
                    'Belum Menikah',
                    'Menikah',
                    'Cerai Hidup',
                    'Cerai Mati',
                ]),
            ],
            /*
            |--------------------------------------------------------------------------
            | WILAYAH
            |--------------------------------------------------------------------------
            */
            'id_provinsi' => [
                'nullable',
                'exists:provinces,id',
            ],
            'id_kota' => [
                'nullable',
                'exists:cities,id',
            ],
            'id_kecamatan' => [
                'nullable',
                'exists:districts,id',
            ],
            'id_kelurahan' => [
                'nullable',
                'exists:urban_villages,id',
            ],
            /*
            |--------------------------------------------------------------------------
            | TANGGAL KEPEGAWAIAN
            |--------------------------------------------------------------------------
            */
            'tanggal_masuk' => [
                // 'required',
                'nullable',
                'date',
                'after:tanggal_lahir',
            ],
            'tmt_pangkat_lama' => [
                'exclude_unless:status_pegawai,PNS',
                'nullable',
                'date',
                'after:tanggal_masuk',
            ],
            'tmt_kgb_lama' => [
                // 'required',
                'nullable',
                'date',
                'after:tanggal_masuk',
            ],
            'tahun_masa_kerja' => [
                // 'required',
                'nullable',
                'integer',
                'min:0',
            ],
            'bulan_masa_kerja' => [
                // 'required',
                'nullable',
                'integer',
                'between:0,11',
            ],
            /*
            |--------------------------------------------------------------------------
            | STATUS PEGAWAI
            |--------------------------------------------------------------------------
            */
            'status' => [
                // 'required',
                'nullable',
                Rule::in([
                    'Aktif',
                    'Nonaktif',
                    'Pensiun',
                ]),
            ],
            /*
            |--------------------------------------------------------------------------
            | FOTO PEGAWAI
            |--------------------------------------------------------------------------
            */
            'foto' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            /*
            |--------------------------------------------------------------------------
            | DOKUMEN KEPEGAWAIAN
            |--------------------------------------------------------------------------
            | Semua dokumen opsional.
            | Maksimal 5 MB per file.
            */
            'dokumen' => [
                'sometimes',
                'array:SK_KGB,SK_PANGKAT,SK_JABATAN,IJAZAH_TERAKHIR,SK_PENGANGKATAN_PNS,SK_PENGANGKATAN_PPPK,KTP',
            ],
            'dokumen.SK_KGB' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
            'dokumen.SK_PANGKAT' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
            'dokumen.SK_JABATAN' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
            'dokumen.IJAZAH_TERAKHIR' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
            'dokumen.SK_PENGANGKATAN_PNS' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
            'dokumen.SK_PENGANGKATAN_PPPK' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
            'dokumen.KTP' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
        ];
    }
    public function messages(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | IDENTITAS PEGAWAI
            |--------------------------------------------------------------------------
            */
            // 'nip.required' => 'NIP wajib diisi.',
            'nip.string' => 'NIP harus berupa teks.',
            'nip.digits' => 'NIP harus terdiri dari 18 digit.',
            'nip.unique' => 'NIP sudah digunakan.',
            // 'nik.required' => 'NIK wajib diisi.',
            'nik.string' => 'NIK harus berupa teks.',
            'nik.digits' => 'NIK harus terdiri dari 16 digit.',
            'nik.unique' => 'NIK sudah digunakan.',
            // 'nama.required' => 'Nama wajib diisi.',
            'nama.string' => 'Nama harus berupa teks.',
            'nama.max' => 'Nama maksimal 100 karakter.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'email.max' => 'Email maksimal 100 karakter.',
            // 'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.string' => 'Nomor HP harus berupa teks.',
            'no_hp.regex' => 'Nomor HP harus diawali 08 dan terdiri dari 10–16 digit.',
            'no_hp.unique' => 'Nomor HP sudah digunakan.',
            /*
            |--------------------------------------------------------------------------
            | KEPEGAWAIAN
            |--------------------------------------------------------------------------
            */
            // 'status_pegawai.required' => 'Status kepegawaian wajib dipilih.',
            'status_pegawai.in' => 'Status kepegawaian harus PNS atau PPPK.',
            'id_pangkat.exists' => 'Pangkat PNS tidak valid.',
            'kode_pppk.exists' => 'Golongan PPPK tidak valid.',
            // 'tingkat_pendidikan.required' => 'Tingkat pendidikan wajib dipilih.',
            'tingkat_pendidikan.exists' => 'Tingkat pendidikan tidak valid.',
            // 'kode_unit_kerja.required' => 'Unit kerja wajib dipilih.',
            'kode_unit_kerja.exists' => 'Unit kerja tidak valid.',
            // 'kode_jabatan.required' => 'Jabatan wajib dipilih.',
            'kode_jabatan.exists' => 'Jabatan tidak valid.',
            /*
            |--------------------------------------------------------------------------
            | DATA PRIBADI
            |--------------------------------------------------------------------------
            */
            // 'tempat_lahir.required' => 'Tempat lahir wajib dipilih.',
            'tempat_lahir.exists' => 'Tempat lahir tidak valid.',
            // 'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            // 'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'jenis_kelamin.in' => 'Jenis kelamin tidak valid.',
            'agama.in' => 'Agama tidak valid.',
            'alamat.string' => 'Alamat harus berupa teks.',
            'status_nikah.in' => 'Status pernikahan tidak valid.',
            /*
            |--------------------------------------------------------------------------
            | WILAYAH
            |--------------------------------------------------------------------------
            */
            'id_provinsi.exists' => 'Provinsi tidak valid.',
            'id_kota.exists' => 'Kota/Kabupaten tidak valid.',
            'id_kecamatan.exists' => 'Kecamatan tidak valid.',
            'id_kelurahan.exists' => 'Kelurahan/Desa tidak valid.',
            /*
            |--------------------------------------------------------------------------
            | TANGGAL KEPEGAWAIAN
            |--------------------------------------------------------------------------
            */
            // 'tanggal_masuk.required' => 'Tanggal mulai bekerja wajib diisi.',
            'tanggal_masuk.date' => 'Format tanggal mulai bekerja tidak valid.',
            'tanggal_masuk.after' => 'Tanggal mulai bekerja harus setelah tanggal lahir.',
            'tmt_pangkat_lama.date' => 'TMT pangkat lama harus berupa tanggal yang valid.',
            'tmt_pangkat_lama.after' => 'TMT pangkat lama harus setelah tanggal mulai bekerja.',
            // 'tmt_kgb_lama.required' => 'TMT KGB lama wajib diisi.',
            'tmt_kgb_lama.date' => 'TMT KGB lama harus berupa tanggal yang valid.',
            'tmt_kgb_lama.after' => 'TMT KGB lama harus setelah tanggal mulai bekerja.',
            // 'tahun_masa_kerja.required' => 'Tahun masa kerja wajib diisi.',
            'tahun_masa_kerja.integer' => 'Tahun masa kerja harus berupa bilangan bulat.',
            'tahun_masa_kerja.min' => 'Tahun masa kerja tidak boleh kurang dari 0.',
            // 'bulan_masa_kerja.required' => 'Bulan masa kerja wajib diisi.',
            'bulan_masa_kerja.integer' => 'Bulan masa kerja harus berupa bilangan bulat.',
            'bulan_masa_kerja.between' => 'Bulan masa kerja harus antara 0 sampai 11.',
            /*
            |--------------------------------------------------------------------------
            | STATUS PEGAWAI
            |--------------------------------------------------------------------------
            */
            // 'status.required' => 'Status pegawai wajib dipilih.',
            'status.in' => 'Status pegawai tidak valid.',
            /*
            |--------------------------------------------------------------------------
            | FOTO PEGAWAI
            |--------------------------------------------------------------------------
            */
            'foto.image' => 'File foto harus berupa gambar.',
            'foto.mimes' => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.',
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
            /*
            |--------------------------------------------------------------------------
            | DOKUMEN KEPEGAWAIAN
            |--------------------------------------------------------------------------
            */
            'dokumen.array' => 'Data dokumen tidak valid.',
            'dokumen.SK_KGB.file' => 'SK KGB harus berupa file yang valid.',
            'dokumen.SK_KGB.mimes' => 'SK KGB harus berformat PDF, JPG, JPEG, atau PNG.',
            'dokumen.SK_KGB.max' => 'Ukuran SK KGB maksimal 5 MB.',
            'dokumen.SK_PANGKAT.file' => 'SK pangkat harus berupa file yang valid.',
            'dokumen.SK_PANGKAT.mimes' => 'SK pangkat harus berformat PDF, JPG, JPEG, atau PNG.',
            'dokumen.SK_PANGKAT.max' => 'Ukuran SK pangkat maksimal 5 MB.',
            'dokumen.SK_JABATAN.file' => 'SK jabatan harus berupa file yang valid.',
            'dokumen.SK_JABATAN.mimes' => 'SK jabatan harus berformat PDF, JPG, JPEG, atau PNG.',
            'dokumen.SK_JABATAN.max' => 'Ukuran SK jabatan maksimal 5 MB.',
            'dokumen.IJAZAH_TERAKHIR.file' => 'Ijazah terakhir harus berupa file yang valid.',
            'dokumen.IJAZAH_TERAKHIR.mimes' => 'Ijazah terakhir harus berformat PDF, JPG, JPEG, atau PNG.',
            'dokumen.IJAZAH_TERAKHIR.max' => 'Ukuran ijazah terakhir maksimal 5 MB.',
            'dokumen.SK_PENGANGKATAN_PNS.file' => 'SK pengangkatan PNS harus berupa file yang valid.',
            'dokumen.SK_PENGANGKATAN_PNS.mimes' => 'SK pengangkatan PNS harus berformat PDF, JPG, JPEG, atau PNG.',
            'dokumen.SK_PENGANGKATAN_PNS.max' => 'Ukuran SK pengangkatan PNS maksimal 5 MB.',
            'dokumen.SK_PENGANGKATAN_PPPK.file' => 'SK pengangkatan PPPK harus berupa file yang valid.',
            'dokumen.SK_PENGANGKATAN_PPPK.mimes' => 'SK pengangkatan PPPK harus berformat PDF, JPG, JPEG, atau PNG.',
            'dokumen.SK_PENGANGKATAN_PPPK.max' => 'Ukuran SK pengangkatan PPPK maksimal 5 MB.',
            'dokumen.KTP.file' => 'KTP harus berupa file yang valid.',
            'dokumen.KTP.mimes' => 'KTP harus berformat PDF, JPG, JPEG, atau PNG.',
            'dokumen.KTP.max' => 'Ukuran KTP maksimal 5 MB.',
        ];
    }
    public function attributes(): array
    {
        return [
            'dokumen.SK_KGB' => 'dokumen SK KGB',
            'dokumen.SK_PANGKAT' => 'dokumen SK pangkat',
            'dokumen.SK_JABATAN' => 'dokumen SK jabatan',
            'dokumen.IJAZAH_TERAKHIR' => 'dokumen ijazah terakhir',
            'dokumen.SK_PENGANGKATAN_PNS' => 'dokumen SK pengangkatan PNS',
            'dokumen.SK_PENGANGKATAN_PPPK' => 'dokumen SK pengangkatan PPPK',
            'dokumen.KTP' => 'dokumen KTP',
        ];
    }
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nip' => trim((string) $this->input('nip', '')),
            'nik' => trim((string) $this->input('nik', '')),
            'nama' => trim((string) $this->input('nama', '')),
            'alamat' => $this->filled('alamat') ? trim((string) $this->input('alamat')) : null,
            'email' => $this->filled('email') ? strtolower(trim((string) $this->input('email'))) : null,
            'no_hp' => $this->formatNoHp($this->input('no_hp')),
        ]);
    }
    protected function formatNoHp(?string $no_hp): ?string
    {
        if (blank($no_hp)) {
            return null;
        }
        $noHp = preg_replace('/\D+/', '', $no_hp);
        if (str_starts_with($noHp, '62')) {
            $noHp = '0' . substr($noHp, 2);
        } elseif (!str_starts_with($noHp, '0')) {
            $noHp = '0' . $noHp;
        }
        return $noHp;
    }
    protected function failedValidation(Validator $validator)
    {
        $redirect = redirect()->back()->withErrors($validator)->withInput();
        if ($this->routeIs('employee.store')) {
            $redirect->with('open_create_modal', true);
        }
        if ($this->routeIs('employee.update')) {
            $employee = $this->route('employee');
            if ($employee instanceof \App\Models\Employee) {
                $redirect->with('edit_employee_nip', $employee->nip);
            } elseif (is_string($employee)) {
                $redirect->with('edit_employee_nip', $employee);
            }
        }
        throw new HttpResponseException($redirect);
    }
}

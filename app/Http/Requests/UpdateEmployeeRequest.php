<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Menentukan hak akses pengguna.
     */
    protected $errorBag = 'employee';
    public function authorize(): bool
    {
        $user = $this->user();
        return $user !== null
            && $user->role === 'user'
            && $user->employee !== null;
    }
    /**
     * Aturan validasi data pegawai.
     */
    public function rules(): array
    {
        $employee = $this->user()->employee;
        $isPns = $this->input('status_pegawai') === 'PNS';
        $isPppk = $this->input('status_pegawai') === 'PPPK';
        return [
            /*
            |--------------------------------------------------------------------------
            | IDENTITAS PEGAWAI
            |--------------------------------------------------------------------------
            */
            'nik' => [
                'required',
                'digits:16',
                Rule::unique('employees', 'nik')
                    ->ignore($employee->nip, 'nip'),
            ],
            'nama' => [
                'required',
                'string',
                'max:100',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('employees', 'email')
                    ->ignore($employee->nip, 'nip'),
            ],
            'no_hp' => [
                'required',
                'string',
                'max:16',
                'regex:/^08[0-9]{8,12}$/',
                Rule::unique('employees', 'no_hp')
                    ->ignore($employee->nip, 'nip'),
            ],
            /*
            |--------------------------------------------------------------------------
            | DATA PRIBADI
            |--------------------------------------------------------------------------
            */
            'tempat_lahir' => [
                'required',
                'string',
                'size:4',
                'exists:cities,id',
            ],
            'tanggal_lahir' => [
                'required',
                'date',
                'before:today',
            ],
            'jenis_kelamin' => [
                'required',
                Rule::in(['Laki-laki', 'Perempuan']),
            ],
            'agama' => [
                'required',
                Rule::in([
                    'Islam',
                    'Kristen Protestan',
                    'Kristen Katolik',
                    'Budha',
                    'Hindu',
                    'Konghucu',
                ]),
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
            'tingkat_pendidikan' => [
                'required',
                'exists:education_levels,kode',
            ],
            'alamat' => [
                'required',
                'string',
            ],
            /*
            |--------------------------------------------------------------------------
            | WILAYAH ADMINISTRATIF
            |--------------------------------------------------------------------------
            */
            'id_provinsi' => [
                'required',
                'string',
                'size:2',
                'exists:provinces,id',
            ],
            'id_kota' => [
                'required',
                'string',
                'size:4',
                'exists:cities,id',
            ],
            'id_kecamatan' => [
                'required',
                'string',
                'size:7',
                'exists:districts,id',
            ],
            'id_kelurahan' => [
                'required',
                'string',
                'size:10',
                'exists:urban_villages,id',
            ],
            /*
            |--------------------------------------------------------------------------
            | INFORMASI KEPEGAWAIAN
            |--------------------------------------------------------------------------
            */
            'status_pegawai' => [
                'required',
                Rule::in(['PNS', 'PPPK']),
            ],
            'tanggal_masuk' => [
                'required',
                'date',
                'after:tanggal_lahir',
            ],
            'kode_unit_kerja' => [
                'required',
                'exists:departments,kode',
            ],
            'kode_jabatan' => [
                'required',
                'exists:positions,kode',
            ],
            'id_pangkat' => [
                Rule::excludeIf(! $isPns),
                'required',
                'exists:ranks,id',
            ],
            'kode_pppk' => [
                Rule::excludeIf(! $isPppk),
                'required',
                'exists:pppk,kode',
            ],
            /*
            |--------------------------------------------------------------------------
            | TANGGAL PANGKAT DAN KGB
            |--------------------------------------------------------------------------
            */
            'tmt_pangkat_lama' => [
                Rule::excludeIf(! $isPns),
                'required',
                'date',
            ],
            // 'tmt_pangkat_baru' => [
            //     Rule::excludeIf(! $isPns),
            //     'required',
            //     'date',
            // ],
            'tmt_kgb_lama' => [
                'required',
                'date',
            ],
            // 'tmt_kgb_baru' => [
            //     'required',
            //     'date',
            // ],
            // /*
            // |--------------------------------------------------------------------------
            // | PENSIUN
            // |--------------------------------------------------------------------------
            // */
            // 'tanggal_pensiun' => [
            //     'required',
            //     'date',
            // ],
            // 'tmt_pensiun' => [
            //     'required',
            //     'date',
            // ],
            /*
            |--------------------------------------------------------------------------
            | MASA KERJA
            |--------------------------------------------------------------------------
            */
            'tahun_masa_kerja' => [
                'required',
                'integer',
                'min:0',
                'max:255',
            ],
            'bulan_masa_kerja' => [
                'required',
                'integer',
                'between:0,11',
            ],
            /*
            |--------------------------------------------------------------------------
            | FOTO PEGAWAI
            |--------------------------------------------------------------------------
            */
            'foto' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
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
    /**
     * Pesan validasi.
     */
    public function messages(): array
    {
        return [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus terdiri dari 16 digit.',
            'nik.unique' => 'NIK sudah digunakan.',
            'nama.required' => 'Nama wajib diisi.',
            'nama.max' => 'Nama maksimal 100 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.regex' => 'Nomor HP harus diawali 08 dan terdiri dari 10 sampai 14 digit.',
            'no_hp.unique' => 'Nomor HP sudah digunakan.',
            'tempat_lahir.required' => 'Tempat lahir wajib dipilih.',
            'tempat_lahir.exists' => 'Tempat lahir tidak valid.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            'jenis_kelamin.in' => 'Jenis kelamin tidak valid.',
            'agama.in' => 'Agama tidak valid.',
            'status_nikah.in' => 'Status pernikahan tidak valid.',
            'tingkat_pendidikan.required' => 'Tingkat pendidikan wajib dipilih.',
            'tingkat_pendidikan.exists' => 'Tingkat pendidikan tidak valid.',
            'alamat.required' => 'Alamat wajib diisi.',
            'id_provinsi.exists' => 'Provinsi tidak valid.',
            'id_kota.exists' => 'Kota/Kabupaten tidak valid.',
            'id_kecamatan.exists' => 'Kecamatan tidak valid.',
            'id_kelurahan.exists' => 'Kelurahan/Desa tidak valid.',
            'status_pegawai.required' => 'Jenis pegawai wajib dipilih.',
            'status_pegawai.in' => 'Jenis pegawai harus PNS atau PPPK.',
            'tanggal_masuk.required' => 'Tanggal masuk wajib diisi.',
            'tanggal_masuk.after' => 'Tanggal masuk harus setelah tanggal lahir.',
            'kode_unit_kerja.required' => 'Unit kerja wajib dipilih.',
            'kode_unit_kerja.exists' => 'Unit kerja tidak valid.',
            'kode_jabatan.required' => 'Jabatan wajib dipilih.',
            'kode_jabatan.exists' => 'Jabatan tidak valid.',
            'id_pangkat.required' => 'Pangkat/Golongan wajib dipilih untuk pegawai PNS.',
            'id_pangkat.exists' => 'Pangkat tidak valid.',
            'kode_pppk.required' => 'Golongan PPPK wajib dipilih untuk pegawai PPPK.',
            'kode_pppk.exists' => 'Golongan PPPK tidak valid.',
            'tmt_pangkat_lama.required' => 'TMT pangkat lama wajib diisi untuk pegawai PNS.',
            // 'tmt_pangkat_baru.required' => 'TMT pangkat baru wajib diisi untuk pegawai PNS.',
            'tmt_kgb_lama.required' => 'TMT KGB lama wajib diisi.',
            // 'tmt_kgb_baru.required' => 'TMT KGB baru wajib diisi.',
            // 'tanggal_pensiun.required' => 'Tanggal pensiun wajib diisi.',
            // 'tmt_pensiun.required' => 'TMT pensiun wajib diisi.',
            'tahun_masa_kerja.required' => 'Tahun masa kerja wajib diisi.',
            'tahun_masa_kerja.integer' => 'Tahun masa kerja harus berupa bilangan bulat.',
            'tahun_masa_kerja.min' => 'Tahun masa kerja tidak boleh kurang dari 0.',
            'tahun_masa_kerja.max' => 'Tahun masa kerja maksimal 255.',
            'bulan_masa_kerja.required' => 'Bulan masa kerja wajib diisi.',
            'bulan_masa_kerja.integer' => 'Bulan masa kerja harus berupa bilangan bulat.',
            'bulan_masa_kerja.between' => 'Bulan masa kerja harus antara 0 sampai 11.',
            'foto.image' => 'File harus berupa gambar.',
            'foto.mimes' => 'Foto harus berformat JPG, JPEG, atau PNG.',
            'foto.max' => 'Ukuran foto maksimal 2 MB.',

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
    /**
     * Normalisasi data sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nik' => $this->filled('nik')
                ? trim((string) $this->input('nik'))
                : null,
            'nama' => trim((string) $this->input('nama', '')),
            'email' => $this->filled('email')
                ? strtolower(trim((string) $this->input('email')))
                : null,
            'no_hp' => $this->formatNoHp($this->input('no_hp')),
            'alamat' => $this->filled('alamat')
                ? trim((string) $this->input('alamat'))
                : null,
        ]);
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
    /**
     * Normalisasi nomor HP menjadi format 08.
     */
    private function formatNoHp(?string $noHp): ?string
    {
        if (blank($noHp)) {
            return null;
        }
        $noHp = preg_replace('/\D+/', '', $noHp);
        if (str_starts_with($noHp, '62')) {
            $noHp = '0' . substr($noHp, 2);
        } elseif (! str_starts_with($noHp, '0')) {
            $noHp = '0' . $noHp;
        }
        return $noHp;
    }
}

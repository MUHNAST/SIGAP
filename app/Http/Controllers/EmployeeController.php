<?php
namespace App\Http\Controllers;
use App\Http\Requests\EmployeeRequest;
use App\Models\City;
use App\Models\District;
use App\Models\Employee;
use App\Models\UrbanVillage;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
class EmployeeController extends Controller
{
    private EmployeeService $employee_service;
    private ActivityLogService $activity_log_service;
    public function __construct(
        EmployeeService $employee_service,
        ActivityLogService $activity_log_service
    ) {
        $this->employee_service = $employee_service;
        $this->activity_log_service = $activity_log_service;
    }
    /**
     * Memastikan pengguna yang login merupakan User SIGAP.
     */
    private function authenticatedUser(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        return $user;
    }
    public function index()
    {
        $user = $this->authenticatedUser();
        $exportGroups = [
            'Data Pegawai' => [
                'nama' => 'Nama Pegawai',
                'nik' => 'NIK',
                'nip' => 'NIP',
                'email' => 'Email',
                'no_hp' => 'Nomor HP',
                'status_pegawai' => 'Jenis Pegawai',
            ],
            'Pangkat dan Golongan' => [
                'pangkat_pns' => 'Pangkat PNS',
                'golongan_pppk' => 'Golongan PPPK',
                'tmt_pangkat_lama' => 'TMT Pangkat Lama',
                'tmt_pangkat_baru' => 'TMT Pangkat Baru',
            ],
            'Kepegawaian' => [
                'jabatan' => 'Jabatan',
                'unit_kerja' => 'Unit Kerja',
                'pendidikan' => 'Pendidikan',
                'tanggal_masuk' => 'Tanggal Masuk',
                'masa_kerja' => 'Masa Kerja',
            ],
            'Data Pribadi' => [
                'tempat_lahir' => 'Tempat Lahir',
                'tanggal_lahir' => 'Tanggal Lahir',
                'jenis_kelamin' => 'Jenis Kelamin',
                'agama' => 'Agama',
                'status_nikah' => 'Status Pernikahan',
                'provinsi' => 'Provinsi',
                'kota' => 'Kota/Kabupaten',
                'kecamatan' => 'Kecamatan',
                'kelurahan' => 'Kelurahan',
                'alamat' => 'Alamat',
            ],
            'Gaji Berkala dan Pensiun' => [
                'tmt_kgb_lama' => 'TMT KGB Lama',
                'tmt_kgb_baru' => 'TMT KGB Baru',
                'tanggal_pensiun' => 'Tanggal Pensiun',
                'tmt_pensiun' => 'TMT Pensiun',
            ],
            'Status Kepegawaian' => [
                'status' => 'Status Pegawai',
            ],
        ];
        return view('employee.index', [
            'title' => 'Data Pegawai',
            'sub_title' => 'Kelola data pegawai sesuai hak akses pengguna',
            'data' => $this->employee_service->getAll($user),
            'user' => $user,
            'exportGroups' => $exportGroups,
            'master' => $this->employee_service->getMasterData($user),
        ]);
    }
    public function show(Employee $employee)
    {
        $user = $this->authenticatedUser();


        $employee = $this->employee_service->getById($employee->nip);
        $this->employee_service->authorizeEmployee($employee);
        return view('employee.show', [
            'title' => 'Detail Data Pegawai',
            'data' => $employee,
            'user' => $user,
        ]);
    }
    public function showForUser()
    {
        $user = $this->authenticatedUser();
        abort_unless($user->role === 'user', 403);
        abort_if(empty($user->nip), 404);
        $employee = $this->employee_service->getById($user->nip);
        abort_if(!$employee, 404);
        $this->employee_service->authorizeEmployee($employee);
        return view('employee.show', [
            'title' => 'Detail Data Pegawai',
            'data' => $employee,
            'user' => $user,
        ]);
    }
    public function store(EmployeeRequest $request)
    {
        $user = $this->authenticatedUser();
        $employee = $this->employee_service->create(
            $request->validated()
        );
        $this->activity_log_service->log(
            'Tambah Data Pegawai',
            $user->nama . ' menambahkan data pegawai ' . $employee->nama
        );
        return redirect()
            ->route('employee.index')
            ->with('success', 'Data pegawai berhasil ditambahkan.');
    }
    public function edit(Employee $employee)
    {
        $user = $this->authenticatedUser();
        $this->employee_service->authorizeEmployee($employee);
        $employee = $this->employee_service->getById($employee->nip);
        $this->employee_service->authorizeEmployee($employee);
        $master = $this->employee_service->getMasterData($user);
        $ranks = $this->employee_service->getRanksByEducation(
            $employee->tingkat_pendidikan,
            $employee->status_pegawai
        );
        return view('employee.edit', [
            'title' => 'Data Pegawai',
            'sub_title' => 'Perbarui data pegawai sesuai hak akses',
            'employee' => $employee,
            'user' => $user,
            'master' => $master,
            'educationLevels' => $master['educationLevels'],
            'ranks' => $ranks,
        ]);
    }
    public function update(EmployeeRequest $request, Employee $employee)
    {
        $user = $this->authenticatedUser();
        $this->employee_service->authorizeEmployee($employee);
        $updatedEmployee = $this->employee_service->update(
            $employee,
            $request->validated()
        );
        $this->activity_log_service->log(
            'Update Data Pegawai',
            $user->nama . ' memperbarui data pegawai ' . $updatedEmployee->nama
        );
        return redirect()
            ->route('employee.index')
            ->with('success', 'Data pegawai berhasil diperbarui.');
    }
    public function getRanksByEducation(
        Request $request,
        string $educationLevelCode
    ) {
        $request->validate([
            'status_pegawai' => [
                'nullable',
                'string',
                'in:PNS,PPPK',
            ],
        ]);
        return response()->json(
            $this->employee_service->getRanksByEducation(
                $educationLevelCode,
                $request->query('status_pegawai')
            )
        );
    }
    public function getCities(string $provinceId)
    {
        return City::where('id_provinsi', $provinceId)
            ->orderBy('nama')
            ->get();
    }
    public function getDistricts(string $cityId)
    {
        return District::where('id_kota', $cityId)
            ->orderBy('nama')
            ->get();
    }
    public function getUrbanVillages(string $districtId)
    {
        return UrbanVillage::where('id_kecamatan', $districtId)
            ->orderBy('nama')
            ->get();
    }
    public function export(string $format)
    {
        $this->authenticatedUser();
        $format = strtolower($format);
        abort_unless(
            in_array($format, ['xlsx', 'csv', 'pdf'], true),
            404,
            'Format ekspor tidak didukung.'
        );
        $columns = request()->input('columns', []);
        abort_unless(
            is_array($columns),
            422,
            'Pilihan kolom tidak valid.'
        );
        $allowedColumns = [
            'nama',
            'nik',
            'nip',
            'email',
            'no_hp',
            'status_pegawai',
            'pangkat_pns',
            'golongan_pppk',
            'jabatan',
            'unit_kerja',
            'pendidikan',
            'tempat_lahir',
            'tanggal_lahir',
            'jenis_kelamin',
            'agama',
            'status_nikah',
            'provinsi',
            'kota',
            'kecamatan',
            'kelurahan',
            'alamat',
            'tanggal_masuk',
            'masa_kerja',
            'tmt_pangkat_lama',
            'tmt_pangkat_baru',
            'tmt_kgb_lama',
            'tmt_kgb_baru',
            'tanggal_pensiun',
            'tmt_pensiun',
            'status',
        ];
        abort_if(
            count($columns) !== count(array_unique($columns)),
            422,
            'Terdapat kolom duplikat.'
        );
        foreach ($columns as $column) {
            abort_unless(
                is_string($column)
                    && in_array($column, $allowedColumns, true),
                422,
                'Pilihan kolom tidak valid.'
            );
        }
        abort_if(
            empty($columns),
            422,
            'Pilih minimal satu kolom.'
        );
        if ($format === 'pdf') {
            abort_if(
                count($columns) > 9,
                422,
                'Ekspor PDF maksimal 9 kolom.'
            );
        }
        return $this->employee_service->export($format, $columns);
    }
    public function import(Request $request)
    {
        $user = $this->authenticatedUser();
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
        ]);
        $file = $request->file('file');
        try {
            $this->employee_service->import($file);
            $this->activity_log_service->log(
                'Import Data Pegawai',
                "{$user->nama} mengimpor data pegawai dari file '{$file->getClientOriginalName()}'."
            );
            return back()->with(
                'success',
                'File selesai diproses. Periksa data pegawai untuk memastikan hasil impor.'
            );
        } catch (\Throwable $e) {
            // Log::error('Import data pegawai gagal.', [
            //     'user_id' => $user->id,
            //     'file' => $file->getClientOriginalName(),
            //     'error' => $e->getMessage(),
            //     'file_error' => $e->getFile(),
            //     'line' => $e->getLine(),
            // ]);
            return back()->with(
                'error',
                'Impor gagal. Periksa format file, data referensi, dan log aplikasi.'
            );
        }
    }
    public function template(string $format)
    {
        $user = $this->authenticatedUser();
        $format = strtolower($format);
        abort_unless(
            in_array($format, ['xlsx', 'csv'], true),
            404,
            'Format template tidak didukung.'
        );
        $response = $this->employee_service->downloadTemplate($format);
        $this->activity_log_service->log(
            'Download Template Pegawai',
            "{$user->nama} mengunduh template impor data pegawai dalam format " . strtoupper($format) . '.'
        );
        return $response;
    }
}

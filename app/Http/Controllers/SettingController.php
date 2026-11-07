<?php
namespace App\Http\Controllers;
use App\Http\Requests\LogoutDevicesRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
class SettingController extends Controller
{
    public function __construct(
        protected SettingService $setting_service,
        protected ActivityLogService $activity_log_service,
        protected NotificationService $notification_service,
    ) {}
    public function index()
{
    $user = Auth::user();
    $employee = $user->employee;

    $master = $employee
        ? $this->setting_service->getMasterData($user)
        : [];

//     dd([
//     'status_pegawai' => $employee->status_pegawai,
//     'id_pangkat_pegawai' => $employee->id_pangkat,
//     'kode_pppk_pegawai' => $employee->kode_pppk,

//     'rank_pertama' => $master['ranks']->first(),
//     'pppk_pertama' => $master['pppk']->first(),
// ]);

    return view('setting.index', [
        'title' => 'Pengaturan',
        'user' => $user,
        'employee' => $employee,
        'master' => $master,
        'lastLogin' => $this->setting_service->getLastLogin($user->id),
        'activities' => $this->setting_service->getActivities($user->id),
    ]);
}
    public function updateProfile(UpdateProfileRequest $request)
    {
        $this->setting_service->updateProfile(Auth::id(), $request->validated());
        $this->activity_log_service->log(
            'Update Profil',
            Auth::user()->nama . ' memperbarui profil.'
        );
        return back()->with('success', 'Profil berhasil diperbarui.')->with('active_tab', 'account');
    }
    public function updateEmployee(UpdateEmployeeRequest $request)
    {
        $employee = $request->user()->employee;
        abort_unless(
            $employee,
            404,
            'Data pegawai tidak ditemukan.'
        );
        $this->setting_service->updateEmployee(
            $employee,
            $request->validated()
        );
        return redirect()
            ->back()
            ->with('success', 'Data pegawai berhasil diperbarui.')
            ->with('active_tab', 'account');
    }
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'foto.required' => 'Foto wajib dipilih.',
            'foto.image'    => 'File harus berupa gambar.',
            'foto.mimes'    => 'Format gambar harus JPG, JPEG, atau PNG.',
            'foto.max'      => 'Ukuran gambar maksimal 2 MB.',
        ]);
        $this->setting_service->updatePhoto($request);
        $this->activity_log_service->log(
            'Update Foto Profil',
            Auth::user()->nama . ' memperbarui foto profil.'
        );
        return back()->with('success', 'Foto profil berhasil diperbarui.')->with('active_tab', 'account');
    }
    public function resetPhoto(Request $request)
    {
        try {
            $this->setting_service->resetPhoto(Auth::id());
            $this->activity_log_service->log(
                'Reset Foto Profil',
                Auth::user()->nama . ' mereset foto profil.'
            );
            return back()->with('success', 'Foto profil berhasil direset.')->with('active_tab', 'account');
        } catch (\Throwable $e) {
            Log::error('Failed to reset profile photo.', [
                'user_id' => Auth::id(),
                'exception_type' => $e::class,
            ]);

            return back()
                ->withErrors([
                    'error' => 'Foto profil gagal direset. Silakan coba kembali.'
                ], 'reset_photo')
                ->with('open_modal', 'modalResetFoto')
                ->with('active_tab', 'account');
        }
    }
    public function updatePassword(UpdatePasswordRequest $request)
    {
        $this->setting_service->updatePassword($request->validated());
        $this->activity_log_service->log(
            'Update Password',
            Auth::user()->nama . ' mengubah password akun.'
        );
        return back()
            ->with('success', 'Password berhasil diperbarui.')
            ->with('active_tab', $request->input('active_tab', 'security'));
    }
    public function logoutDevices(LogoutDevicesRequest $request)
    {
        try {
            $this->setting_service->logoutDevices($request->validated());
            $this->activity_log_service->log(
                'Logout Semua Perangkat',
                Auth::user()->nama . ' logout dari seluruh perangkat.'
            );
            return back()->with('success', 'Berhasil logout dari seluruh perangkat.')->with('active_tab', 'security');
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors(), 'logout')
                ->with('open_modal', 'modalLogoutDevices')
                ->with('active_tab', 'security');
        } catch (\Throwable $e) {
            Log::error('Failed to log out other devices.', [
                'user_id' => Auth::id(),
                'exception_type' => $e::class,
            ]);

            return back()->withErrors([
                'error' => 'Perangkat lain gagal dikeluarkan. Silakan coba kembali.'
            ], 'logout')->with('open_modal', 'modalLogoutDevices')->with('active_tab', 'security');
        }
    }
    /**
     * Mengaktifkan 2FA melalui email.
     */
    public function enableTwoFactor(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
        ]);
        $this->setting_service->enableTwoFactor($data);
        $this->activity_log_service->log(
            'Aktivasi 2FA',
            Auth::user()->nama . ' mengaktifkan verifikasi dua langkah melalui email.'
        );
        return back()
            ->with('active_tab', 'security')
            ->with('success', 'Verifikasi dua langkah melalui email berhasil diaktifkan.');
    }
    /**
     * Menonaktifkan 2FA.
     */
    public function disableTwoFactor(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
        ]);
        $this->setting_service->disableTwoFactor($data);
        $this->activity_log_service->log(
            'Nonaktifkan 2FA',
            Auth::user()->nama . ' menonaktifkan verifikasi dua langkah melalui email.'
        );
        return back()
            ->with('active_tab', 'security')
            ->with('success', 'Verifikasi dua langkah berhasil dinonaktifkan.');
    }
}

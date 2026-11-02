<?php
namespace App\Http\Controllers;
use App\Http\Requests\UserRequest;
use App\Services\UserService;
use App\Services\ActivityLogService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
class UserController extends Controller
{
    public function __construct(
        private UserService $user_service,
        private ActivityLogService $activity_log_service
    ) {}
    /**
     * Menampilkan data pengguna sesuai hak akses.
     */
    public function index()
    {
        $userLogin = Auth::user();
        $data = $this->user_service->getAll();
        // Ambil unit kerja yang tersedia untuk setiap pengguna.
        foreach ($data as $user) {
            $user->available_departments = $this->user_service
                ->getAvailableDepartment($user->id);
        }
        // Unit kerja untuk form tambah hanya tersedia bagi Super Admin.
        $departments = $userLogin->role === 'super_admin' ? $this->user_service->getAvailableDepartment() : collect();
        return view('user.index', [
            'title' => 'Data Admin dan User',
            'sub_title' => $userLogin->role === 'super_admin'
                ? 'Kelola Seluruh Data Pengguna Dalam Sistem'
                : 'Data Pengguna Unit Kerja',
            'data' => $data,
            'user' => $userLogin,
            'departments' => $departments,
        ]);
    }
    /**
     * Menambahkan akun Admin.
     * Hanya Super Admin yang dapat melakukannya.
     */
    public function store(UserRequest $request)
    {
        try {
            $user = $this->user_service->create($request->validated());
            $this->activity_log_service->log(
                'Tambah Pengguna',
                Auth::user()->nama . " menambahkan admin {$user->nama}"
            );
            return redirect()
                ->route('user.index')
                ->with(
                    'success',
                    'Admin berhasil ditambahkan. Password default: 123456789.'
                );
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (QueryException $e) {
            return back()
                ->withInput()
                ->with('danger', 'Terjadi kesalahan saat menyimpan data.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('danger', $e->getMessage());
        }
    }
    /**
     * Memperbarui data pengguna.
     * Hanya Super Admin yang dapat melakukannya.
     */
    public function update(UserRequest $request, int $id)
    {
        try {
            $user = $this->user_service->getById($id);
            $this->user_service->update(
                $user,
                $request->validated()
            );
            $this->activity_log_service->log(
                'Update Pengguna',
                Auth::user()->nama . " mengubah akun {$user->nama}"
            );
            return back()->with(
                'success',
                'Data pengguna berhasil diperbarui.'
            );
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()
                ->withErrors([
                    'error' => $e->getMessage(),
                ], 'edit')
                ->withInput()
                ->with('open_modal', 'modalEditUser' . $id);
        }
    }
    /**
     * Mereset password pengguna.
     *
     * Super Admin dapat mereset seluruh akun.
     * Admin hanya dapat mereset akun User di unit kerjanya.
     */
    public function resetPassword(int $id)
    {
        try {
            $user = $this->user_service->getById($id);
            $this->user_service->resetPassword($user);
            $this->activity_log_service->log(
                'Reset Password',
                Auth::user()->nama . " mereset password {$user->nama}"
            );
            return back()->with(
                'success',
                'Password pengguna berhasil direset menjadi 123456789.'
            );
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        }
    }
    /**
     * Menghapus pengguna.
     * Hanya Super Admin yang dapat melakukannya.
     */
    // public function destroy(int $id)
    // {
    //     try {
    //         $user = $this->user_service->getById($id);
    //         $this->user_service->delete($user);
    //         $this->activity_log_service->log(
    //             'Hapus Pengguna',
    //             Auth::user()->nama . " menghapus akun {$user->nama}"
    //         );
    //         return back()->with(
    //             'success',
    //             'Data pengguna berhasil dihapus.'
    //         );
    //     } catch (AuthorizationException $e) {
    //         throw $e;
    //     } catch (ModelNotFoundException $e) {
    //         throw $e;
    //     } catch (\Throwable $e) {
    //         return back()
    //             ->withErrors([
    //                 'error' => $e->getMessage(),
    //             ], 'delete')
    //             ->withInput()
    //             ->with('open_modal', 'modalHapusUser' . $id);
    //     }
    // }
}

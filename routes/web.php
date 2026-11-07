<?php
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\PppkController;
use App\Http\Controllers\RankController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TwoFactorChallengeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
//  1. HALAMAN UTAMA DAN AUTENTIKASI
// Pengalihan dashboard berdasarkan role.
Route::get('/', function () {
    if (!Auth::check()) {
        return app(AuthController::class)->index();
    }
    $dashboardRoute = match (Auth::user()->role) {
        'super_admin', 'admin' => 'dashboard',
        'user' => 'dashboard_user',
        default => null,
    };
    if ($dashboardRoute === null) {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login')
            ->withErrors([
                'username' => 'Role akun tidak memiliki akses ke sistem.',
            ]);
    }
    return redirect()->route($dashboardRoute);
})->name('login');
// Proses login dan verifikasi 2FA.
Route::middleware(['guest', 'throttle:login'])->group(function () {
    Route::post('/login/process', [AuthController::class, 'login'])->name('login.process');
    Route::prefix('auth-two-factor-challenge')->name('auth.two-factor.')->controller(TwoFactorChallengeController::class)->group(function () {
        Route::get('/', 'create')->name('challenge');
        Route::post('/', 'store')->name('verify');
        Route::post('/resend', 'resend')->name('resend');
    });
});
// Logout.
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
//  2. ROUTE SELURUH ROLE
//  Berlaku untuk Super Admin, Admin, dan User.
Route::middleware(['auth', 'role:super_admin,admin,user'])->group(function () {
    //  2.1. Pengaturan Akun
    Route::prefix('setting')->name('setting.')->controller(SettingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::put('/account', 'updateProfile')->name('account.update');
        Route::put('/password', 'updatePassword')->name('password.update');
        Route::put('/photo', 'updatePhoto')->name('photo.update');
        Route::delete('/photo', 'resetPhoto')->name('photo.reset');
        Route::post('/logout-devices', 'logoutDevices')->name('logout.devices');
        // Pengaturan autentikasi dua faktor.
        Route::prefix('two-factor')->name('two-factor.')->group(function () {
            Route::post('/enable', 'enableTwoFactor')->name('enable');
            Route::post('/confirm', 'confirmTwoFactor')->name('confirm');
            Route::delete('/disable', 'disableTwoFactor')->name('disable');
        });
        // Perubahan data pegawai untuk akun User.
        Route::put('/employee', 'updateEmployee')->middleware('role:user')->name('employee.update');
        // Perubahan data pegawai berdasarkan ID.
        Route::put('/employee/{employee}', 'updateEmployee')->name('employee.update.by-id');
    });
    //  2.2. Dokumen Pegawai
    Route::prefix('employees/{employee}/documents')->name('employee.documents.')->controller(EmployeeDocumentController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{document}', 'show')->name('show');
        Route::get('/{document}/edit', 'edit')->name('edit');
        Route::put('/{document}', 'update')->name('update');
        Route::delete('/{document}', 'destroy')->name('destroy');
    });
    //  2.3. Notifikasi
    Route::prefix('notification')->name('notification.')->controller(NotificationController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/unread-count', 'unreadCount')->name('unread-count');
        Route::put('/read-all', 'markAllAsRead')->name('read-all');
        Route::put('/{id}/read', 'markAsRead')->name('read');
        Route::post('/{id}', 'read')->name('read.notification');
    });
    //  2.4. Referensi Formulir Pegawai
    // Data wilayah.
    Route::prefix('')->controller(EmployeeController::class)->group(function () {
        Route::get('/cities/{province}', 'getCities')->name('employee.cities');
        Route::get('/districts/{city}', 'getDistricts')->name('employee.districts');
        Route::get('/urban-villages/{district}', 'getUrbanVillages')->name('employee.urban-villages');
        // Pangkat berdasarkan tingkat pendidikan.
        Route::get('/employee/ranks-by-education/{educationLevelCode}','getRanksByEducation')->name('employee.ranks-by-education');
    });
});
//  3. ROUTE ADMIN DAN SUPER ADMIN
//  Dashboard, log aktivitas, dan pengelolaan data operasional.
Route::middleware(['auth', 'role:super_admin,admin'])->group(function () {
    //  3.1. Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/card/{jenis}', [CardController::class, 'index'])->whereIn('jenis', ['kp', 'kgb', 'mp'])->name('card.index');
    //  3.2. Log Aktivitas
    Route::resource('activityLog', ActivityLogController::class)->only(['index', 'show']);
    //  3.3. Data Pegawai
    Route::prefix('employee')->name('employee.')->controller(EmployeeController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:pegawai.view')->name('index');
        Route::get('/{employee}', 'show')->middleware('permission:pegawai.view')->name('show');
        Route::post('/', 'store')->middleware('permission:pegawai.create')->name('store');
        Route::get('/{employee}/edit', 'edit')->middleware('permission:pegawai.update')->name('edit');
        Route::put('/{employee}', 'update')->middleware('permission:pegawai.update')->name('update');
            // Export data pegawai.
        Route::get('/export/{format}', 'export')->middleware('permission:pegawai.export')->whereIn('format', ['csv', 'xlsx', 'pdf'])->name('export');
            // Import dan template data pegawai.
        Route::post('/import', 'import')->middleware('permission:pegawai.import_template')->name('import');
        Route::get('/template/{format}', 'template')->middleware('permission:pegawai.import_template')->whereIn('format', ['csv', 'xlsx'])->name('template');
    });
    //  3.4. Unit Kerja
    Route::prefix('department')->name('department.')->controller(DepartmentController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:unit_kerja.view')->name('index');
        Route::get('/create', 'create')->middleware('permission:unit_kerja.create')->name('create');
        Route::post('/', 'store')->middleware('permission:unit_kerja.create')->name('store');
        // Route::get('/{department}', 'show')->middleware('permission:unit_kerja.view')->name('show');
        Route::get('/{department}/edit', 'edit')->middleware('permission:unit_kerja.update')->name('edit');
        Route::put('/{department}', 'update')->middleware('permission:unit_kerja.update')->name('update');
        Route::delete('/{department}', 'destroy')->middleware('permission:unit_kerja.update')->name('destroy');
    });
    //  3.5. Jabatan
    Route::prefix('position')->name('position.')->controller(PositionController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:jabatan.view')->name('index');
        Route::get('/create', 'create')->middleware('permission:jabatan.create')->name('create');
        Route::post('/', 'store')->middleware('permission:jabatan.create')->name('store');
        // Route::get('/{position}', 'show')->middleware('permission:jabatan.view')->name('show');
        Route::get('/{position}/edit', 'edit')->middleware('permission:jabatan.update')->name('edit');
        Route::put('/{position}', 'update')->middleware('permission:jabatan.update')->name('update');
        Route::delete('/{position}', 'destroy')->middleware('permission:jabatan.update')->name('destroy');
    });
    //  3.6. Pangkat dan Golongan
    Route::prefix('rank')->name('rank.')->controller(RankController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:pangkat.view')->name('index');
        Route::get('/create', 'create')->middleware('permission:pangkat.create')->name('create');
        Route::post('/', 'store')->middleware('permission:pangkat.create')->name('store');
        // Route::get('/{rank}', 'show')->middleware('permission:pangkat.view')->name('show');
        Route::get('/{rank}/edit', 'edit')->middleware('permission:pangkat.update')->name('edit');
        Route::put('/{rank}', 'update')->middleware('permission:pangkat.update')->name('update');
        Route::delete('/{rank}', 'destroy')->middleware('permission:pangkat.update')->name('destroy');
    });
    //  3.7. Golongan PPPK
    Route::prefix('pppk')->name('pppk.')->controller(PppkController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:pppk.view')->name('index');
        Route::get('/create', 'create')->middleware('permission:pppk.create')->name('create');
        Route::post('/', 'store')->middleware('permission:pppk.create')->name('store');
        // Route::get('/{pppk}', 'show')->middleware('permission:pppk.view')->name('show');
        Route::get('/{pppk}/edit', 'edit')->middleware('permission:pppk.update')->name('edit');
        Route::put('/{pppk}', 'update')->middleware('permission:pppk.update')->name('update');
        Route::delete('/{pppk}', 'destroy')->middleware('permission:pppk.update')->name('destroy');
    });
    //  3.8. Data Gaji
    Route::prefix('salary')->name('salary.')->controller(SalaryController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:gaji.view')->name('index');
        Route::get('/create', 'create')->middleware('permission:gaji.create')->name('create');
        Route::post('/', 'store')->middleware('permission:gaji.create')->name('store');
        // Route::get('/{salary}', 'show')->middleware('permission:gaji.view')->name('show');
        Route::get('/{salary}/edit', 'edit')->middleware('permission:gaji.update')->name('edit');
        Route::put('/{salary}', 'update')->middleware('permission:gaji.update')->name('update');
        Route::delete('/{salary}', 'destroy')->middleware('permission:gaji.update')->name('destroy');
    });
    //  3.9. Laporan KGB
    Route::prefix('report')->name('report.')->controller(ReportController::class)->group(function () {
        Route::get('/kgb', 'index')->middleware('permission:laporan_kgb.manage')->name('kgb');
        Route::get('/preview/{id}', 'preview')->middleware('permission:laporan_kgb.manage')->name('preview');
        Route::post('/print/{id}', 'print')->middleware('permission:laporan_kgb.manage')->name('print');
    });
    //  3.10. Riwayat Laporan KGB
    Route::prefix('history')->name('history.')->controller(ReportController::class)->group(function () {
        Route::get('/', 'history')->middleware('permission:riwayat_kgb.manage')->name('kgb');
        Route::get('/{history}/preview', 'historyPreview')->middleware('permission:riwayat_kgb.manage')->name('preview');
        Route::get('/{history}/print', 'historyPrint')->middleware('permission:riwayat_kgb.manage')->name('print');
    });
    //  3.11. Data Pengguna
    Route::get('/user', [UserController::class, 'index'])->middleware('permission:user.view')->name('user.index');
    Route::put('/user/{id}/reset-password', [UserController::class, 'resetPassword'])->middleware('permission:user.reset_password')->name('user.reset-password');
});
//  4. KHUSUS SUPER ADMIN
//  Pengelolaan akun pengguna dan manajemen hak akses.
Route::middleware(['auth', 'role:super_admin'])->group(function () {
    //  4.1. Pengelolaan Data Pengguna
    Route::resource('user', UserController::class)->except(['index']);
    //  4.2. Manajemen Hak Akses Admin
    Route::prefix('setting/role-permissions')->name('setting.role-permissions.')->controller(RolePermissionController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::put('/', 'update')->name('update');
    });
});
//  5. KHUSUS USER
//  Dashboard dan fitur yang disediakan untuk pegawai.
Route::middleware(['auth', 'role:user'])->group(function () {
    //  5.1. Dashboard User
    Route::get('/dashboard-user', [DashboardController::class, 'dashboardUser'])->name('dashboard_user');
    //  5.2. Detail Pegawai
    Route::get('/detail-pegawai', [EmployeeController::class, 'showForUser'])->name('employe.detail');
    //  5.3. Log Aktivitas
    Route::get('/riwayat-aktivitas', [ActivityLogController::class, 'showForUser'])->name('activityLog.user');
    //  5.4. Laporan KGB User
    Route::prefix('laporan-kgb-user')->name('report.kgb.user')->controller(ReportController::class)->group(function () {
        Route::get('/', 'indexUser')->name('');
        Route::get('/preview/{nip}', 'previewUser')->name('.preview');
        Route::post('/cetak/{nip}', 'printUser')->name('.print');
    });
});

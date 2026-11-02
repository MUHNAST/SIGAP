<?php
namespace App\Http\Controllers;
use App\Services\ActivityLogService;
use App\Services\EmployeeService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class ReportController extends Controller
{
    protected ReportService $report_service;
    protected EmployeeService $employee_service;
    protected ActivityLogService $activity_log_service;
    public function __construct(
        EmployeeService $employee_service,
        ReportService $report_service,
        ActivityLogService $activity_log_service
    ) {
        $this->report_service = $report_service;
        $this->employee_service = $employee_service;
        $this->activity_log_service = $activity_log_service;
    }
    // Daftar laporan KGB.
    public function index(Request $request)
    {
        $user = Auth::user();
        $title = 'Laporan Kenaikan Gaji Berkala';
        $sub_title = 'Daftar pegawai yang akan memperoleh kenaikan gaji berkala dalam 2 bulan ke depan';
        $filter = $request->only([
            'search',
            'department',
        ]);
        $data = $this->report_service->getLaporanKGB($filter);
        $lastUpdate = $data->max('updated_at');
        $jumlahKgb = $data->count();
        return view('report.index', [
            'title' => $title,
            'sub_title' => $sub_title,
            'data' => $data,
            'jumlahKgb' => $jumlahKgb,
            'lastUpdate' => $lastUpdate,
            'user' => $user,
        ]);
    }
    // Preview surat KGB.
    public function preview(string $nip)
    {
        $title = 'Preview Surat KGB';
        $employee = $this->report_service
            ->getEmployeeForKgbPreview($nip);
        $previousHistory = $this->report_service
            ->getPreviousHistory($employee);
        $surat = $this->report_service->prepareSuratData(
            $employee,
            null,
            false
        );
        return view('report.preview', [
            'title' => $title,
            'employee' => $employee,
            'surat' => $surat,
            'previousHistory' => $previousHistory,
            'user' => Auth::user(),
            'camat' => $this->report_service->getCamat(),
            'isHistory' => false,
        ]);
    }
    // Memproses cetak surat KGB untuk Super Admin dan Admin.
    public function print(Request $request, string $nip)
    {
        $existingHistory = $this->report_service
            ->getExistingKgbHistory($nip);
        if ($existingHistory) {
            return redirect()
                ->route('report.kgb')
                ->with(
                    'warning',
                    'Surat KGB untuk periode ini sudah pernah dicetak.'
                );
        }
        $history = $this->report_service->printKgb(
            $nip,
            $request->only([
                'nomor_sk_lama',
                'nomor_sk_baru',
                'tanggal_sk_lama',
                'tanggal_sk_baru',
                'pejabat',
                'tmt_kgb_lama',
                'tmt_kgb_baru',
                'tahun_masa_kerja_lama',
                'bulan_masa_kerja_lama',
                'tahun_masa_kerja_baru',
                'bulan_masa_kerja_baru',
                'gaji_pokok_lama',
                'gaji_pokok_baru',
                'dasar_hukum_kgb',
            ])
        );
        if (! $history) {
            abort(404);
        }
        $user = Auth::user();
        $this->activity_log_service->log(
            'Cetak Surat KGB',
            "{$user->nama} mencetak surat KGB pegawai {$history->employee->nama}."
        );
        $previousHistory = $this->report_service
            ->getPreviousHistory(
                $history->employee,
                $history
            );
        $camat = $this->report_service->getCamat();
        $surat = $this->report_service->prepareSuratData(
            $history->employee,
            $history
        );
        return response()
            ->view('report.print', [
                'employee' => $history->employee,
                'history' => $history,
                'previousHistory' => $previousHistory,
                'surat' => $surat,
                'camat' => $camat,
                'user' => $user,
                'isHistory' => false,
            ])
            ->header(
                'Cache-Control',
                'no-store, no-cache, must-revalidate, max-age=0'
            )
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
    // Daftar riwayat KGB.
    public function history()
    {
        $title = 'Riwayat Kenaikan Gaji Berkala';
        $sub_title = 'Seluruh Surat Kenaikan Gaji Berkala yang telah diproses dan dicetak';
        $histories = $this->report_service->getHistory();
        $user = Auth::user();
        $cetakanTerakhir = $histories->max('created_at');
        $periodeAwal = $histories
            ->pluck('tmt_kgb_lama')
            ->filter()
            ->map(fn ($date) => $date->year)
            ->min();
        $periodeAkhir = $histories
            ->pluck('tmt_kgb_baru')
            ->filter()
            ->map(fn ($date) => $date->year)
            ->max();
        return view('report.history', [
            'title' => $title,
            'histories' => $histories,
            'cetakanTerakhir' => $cetakanTerakhir,
            'periodeAwal' => $periodeAwal,
            'periodeAkhir' => $periodeAkhir,
            'user' => $user,
            'jumlahHistory' => $histories->count(),
        ]);
    }
    // Preview riwayat surat KGB.
    public function historyPreview(int $id)
    {
        $title = 'Preview Riwayat KGB';
        $history = $this->report_service->getHistoryById($id);
        $camat = $this->report_service->getCamat();
        $user = Auth::user();
        $surat = $this->report_service->prepareSuratData(
            $history->employee,
            $history,
            true
        );
        return view('report.previewHistory', [
            'title' => $title,
            'employee' => $history->employee,
            'history' => $history,
            'camat' => $camat,
            'user' => $user,
            'surat' => $surat,
            'isHistory' => true,
        ]);
    }
    // Cetak kembali surat dari riwayat KGB.
    public function historyPrint(int $id)
    {
        $history = $this->report_service->getHistoryById($id);
        $camat = $this->report_service->getCamat();
        $user = Auth::user();
        $surat = $this->report_service->prepareSuratData(
            $history->employee,
            $history,
            true
        );
        $this->activity_log_service->log(
            'Cetak Surat KGB',
            "{$user->nama} mencetak surat KGB pegawai {$history->employee->nama}."
        );
        return view('report.printHistory', [
            'history' => $history,
            'employee' => $history->employee,
            'camat' => $camat,
            'user' => $user,
            'surat' => $surat,
            'isHistory' => true,
        ]);
    }
    // Daftar laporan KGB khusus User.
    public function indexUser(Request $request)
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->role === 'user' && filled($user->nip),
            403,
            'Akun Anda belum terhubung dengan data pegawai.'
        );
        $title = 'Laporan Kenaikan Gaji Berkala';
        $sub_title = 'Informasi jadwal kenaikan gaji berkala Anda dalam 2 bulan ke depan';
        $filter = $request->only(['search']);
        $data = $this->report_service
            ->getLaporanKGBForUser($filter);
        return view('report.showUser', [
            'title' => $title,
            'sub_title' => $sub_title,
            'data' => $data,
            'jumlahKgb' => $data->count(),
            'lastUpdate' => $data->max('updated_at'),
            'user' => $user,
        ]);
    }
    // Preview surat KGB khusus User.
    public function previewUser(string $nip)
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->role === 'user' && filled($user->nip),
            403,
            'Anda tidak memiliki akses ke fitur ini.'
        );
        $employee = $this->report_service
            ->getEmployeeForUserKgbPreview($nip);
        $previousHistory = $this->report_service
            ->getPreviousHistory($employee);
        $surat = $this->report_service->prepareSuratData(
            $employee,
            null,
            false
        );
        return response()
            ->view('report.preview', [
                'title' => 'Preview Surat Kenaikan Gaji Berkala',
                'employee' => $employee,
                'surat' => $surat,
                'previousHistory' => $previousHistory,
                'history' => null,
                'user' => $user,
                'camat' => $this->report_service->getCamat(),
                'isHistory' => false,
            ])
            ->header(
                'Cache-Control',
                'no-store, no-cache, must-revalidate, max-age=0'
            )
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
    // Memproses cetak surat KGB khusus User.
    public function printUser(Request $request, string $nip)
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->role === 'user' && filled($user->nip),
            403,
            'Anda tidak memiliki akses ke fitur ini.'
        );
        $history = $this->report_service->printKgbForUser(
            $nip,
            $request->only([
                'nomor_sk_lama',
                'nomor_sk_baru',
                'tanggal_sk_lama',
                'tanggal_sk_baru',
                'pejabat',
                'tmt_kgb_lama',
                'tmt_kgb_baru',
                'tahun_masa_kerja_lama',
                'bulan_masa_kerja_lama',
                'tahun_masa_kerja_baru',
                'bulan_masa_kerja_baru',
                'gaji_pokok_lama',
                'gaji_pokok_baru',
                'dasar_hukum_kgb',
            ])
        );
        if (! $history) {
            return redirect()
                ->route('report.kgb.user')
                ->with(
                    'warning',
                    'Surat KGB tidak dapat diproses. Periksa kelayakan atau status pemrosesan KGB.'
                );
        }
        $this->activity_log_service->log(
            'Cetak Surat KGB',
            "{$user->nama} mencetak surat KGB miliknya."
        );
        $previousHistory = $this->report_service
            ->getPreviousHistory(
                $history->employee,
                $history
            );
        $surat = $this->report_service->prepareSuratData(
            $history->employee,
            $history
        );
        return response()
            ->view('report.print', [
                'employee' => $history->employee,
                'history' => $history,
                'previousHistory' => $previousHistory,
                'surat' => $surat,
                'camat' => $this->report_service->getCamat(),
                'user' => $user,
                'isHistory' => false,
            ])
            ->header(
                'Cache-Control',
                'no-store, no-cache, must-revalidate, max-age=0'
            )
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}

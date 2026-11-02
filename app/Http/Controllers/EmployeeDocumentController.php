<?php
namespace App\Http\Controllers;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
class EmployeeDocumentController extends Controller
{
    public function show(string $employee, string $document)
    {
        $pengguna = Auth::user();
        abort_unless($pengguna, 401);
        $pegawai = Employee::query()
            ->where('nip', $employee)
            ->firstOrFail();
        // Super Admin dapat mengakses dokumen semua pegawai.
        if ($pengguna->role === 'super_admin') {
            // Tidak perlu pembatasan unit kerja.
        } elseif ($pengguna->role === 'admin') {
            // Admin hanya dapat mengakses pegawai dalam unit kerjanya.
            abort_unless(
                filled($pengguna->kode_unit_kerja)
                    && $pengguna->kode_unit_kerja === $pegawai->kode_unit_kerja,
                403,
                'Anda tidak memiliki akses ke dokumen pegawai ini.'
            );
        } elseif ($pengguna->role === 'user') {
            // User hanya dapat mengakses dokumen miliknya sendiri.
            abort_unless(
                filled($pengguna->nip)
                    && (string) $pengguna->nip === (string) $pegawai->nip,
                403,
                'Anda hanya dapat mengakses dokumen milik sendiri.'
            );
        } else {
            abort(403, 'Anda tidak memiliki akses ke dokumen pegawai ini.');
        }
        // Pastikan dokumen benar-benar milik pegawai pada URL.
        $dokumen = EmployeeDocument::query()
            ->where('nip', $pegawai->nip)
            ->whereKey($document)
            ->firstOrFail();
        $path = $dokumen->nama_file;
        // Cegah path traversal dan path absolut.
        abort_if(
            ! is_string($path)
                || $path === ''
                || str_contains($path, '..')
                || str_starts_with($path, '/')
                || str_starts_with($path, '\\')
                || preg_match('/^[a-zA-Z]:/', $path),
            403,
            'Lokasi dokumen tidak valid.'
        );
        $storage = Storage::disk('local');
        abort_unless(
            $storage->exists($path),
            404,
            'File dokumen tidak ditemukan.'
        );
        $extension = strtolower(
            pathinfo($dokumen->nama_file_asli, PATHINFO_EXTENSION)
        );
        $mimeType = match ($extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => null,
        };
        abort_if(
            $mimeType === null,
            403,
            'Format dokumen tidak diizinkan.'
        );
        $namaFile = basename($dokumen->nama_file_asli);
        return response()->file(
            $storage->path($path),
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' .
                    addcslashes($namaFile, '"\\') . '"',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
                'X-Frame-Options' => 'SAMEORIGIN',
            ]
        );
    }
}

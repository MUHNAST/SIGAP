<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\EmployeeService;
class SyncEmployeeTmt extends Command
{
    protected $signature = 'employee:sync-tmt';
    protected $deskripsi = 'Sinkronisasi TMT pegawai';
    public function handle(EmployeeService $employeeService)
    {
        $employeeService->syncTmt();
        $this->info('TMT pegawai berhasil disinkronisasi.');
    }
}

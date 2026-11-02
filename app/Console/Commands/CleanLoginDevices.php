<?php
namespace App\Console\Commands;
use App\Models\LoginDevice;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
#[Signature('app:clean-login-devices')]
#[Description('Membersihkan perangkat login yang tidak aktif lebih dari 1 hari')]
class CleanLoginDevices extends Command
{
    public function handle(): int
    {
        $deleted = LoginDevice::query()->where('aktivitas_terakhir', '<', now()->subHours())->delete();
        $this->info(
            "{$deleted} perangkat login berhasil dibersihkan."
        );
        return self::SUCCESS;
    }
}

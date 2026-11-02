<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckHostingDatabase extends Command
{
    protected $signature = 'hosting:check-database';

    protected $description = 'Memeriksa tabel dan riwayat migration database hosting';

    public function handle(): int
    {
        $this->info('Memeriksa koneksi database...');

        try {
            $this->info('Database: ' . DB::connection()->getDatabaseName());

            if (!Schema::hasTable('migrations')) {
                $this->error('Tabel migrations belum tersedia.');

                return self::FAILURE;
            }

            $this->info('Daftar migration yang tercatat:');

            $migrations = DB::table('migrations')
                ->orderBy('batch')
                ->orderBy('migration')
                ->get();

            foreach ($migrations as $migration) {
                $this->line(
                    $migration->batch . ' | ' . $migration->migration
                );
            }

            $this->newLine();

            $this->info('Pemeriksaan tabel employees:');

            $this->line(
                'Tabel employees: ' .
                (Schema::hasTable('employees') ? 'ADA' : 'TIDAK ADA')
            );

            $this->line(
                'Migration create_employees_table: ' .
                ($migrations->contains(
                    fn ($item) => $item->migration ===
                    '2026_06_24_030713_create_employees_table'
                ) ? 'TERCATAT' : 'BELUM TERCATAT')
            );

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}

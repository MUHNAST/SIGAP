<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use JsonException;

class WilayahSeeder extends Seeder
{
    private const CHUNK_SIZE = 500;

    public function run(): void
    {
        $basePath = storage_path('app/wilayah-indonesia');

        $provinsiPath = "{$basePath}/provinsi/provinsi.json";

        $kabFiles = File::glob(
            "{$basePath}/kabupaten_kota/kab-*.json"
        );

        $kecFiles = File::glob(
            "{$basePath}/kecamatan/kec-*.json"
        );

        $kelFiles = File::glob(
            "{$basePath}/kelurahan_desa/keldesa-*.json"
        );

        // Pastikan seluruh sumber data tersedia.
        foreach ([
            $provinsiPath,
            ...$kabFiles,
            ...$kecFiles,
            ...$kelFiles,
        ] as $file) {
            if (!File::exists($file)) {
                throw new RuntimeException(
                    "File wilayah tidak ditemukan: {$file}"
                );
            }
        }

        if (
            empty($kabFiles) ||
            empty($kecFiles) ||
            empty($kelFiles)
        ) {
            throw new RuntimeException(
                'File data wilayah belum lengkap.'
            );
        }

        // Periksa seluruh JSON sebelum menghapus data lama.
        $this->readJson($provinsiPath);

        foreach ($kabFiles as $file) {
            $this->readJson($file);

            if (!preg_match('/kab-(\d+)/', basename($file), $match)) {
                throw new RuntimeException("Nama file tidak valid: {$file}");
            }
        }

        foreach ($kecFiles as $file) {
            $this->readJson($file);

            if (!preg_match('/kec-(\d+)-(\d+)/', basename($file), $match)) {
                throw new RuntimeException("Nama file tidak valid: {$file}");
            }
        }

        foreach ($kelFiles as $file) {
            $this->readJson($file);

            if (!preg_match(
                '/keldesa-(\d+)-(\d+)-(\d+)/',
                basename($file),
                $match
            )) {
                throw new RuntimeException("Nama file tidak valid: {$file}");
            }
        }

        // Kosongkan tabel setelah seluruh sumber data lolos pemeriksaan.
        $this->clearTables();

        $now = now();

        /*
        |--------------------------------------------------------------------------
        | PROVINCES
        |--------------------------------------------------------------------------
        */

        $provinsi = $this->readJson($provinsiPath);
        $rows = [];

        foreach ($provinsi as $id => $nama) {
            $rows[] = [
                'id' => (string) $id,
                'nama' => $nama,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->insertChunks('provinces', $rows);

        /*
        |--------------------------------------------------------------------------
        | CITIES
        |--------------------------------------------------------------------------
        */

        foreach ($kabFiles as $file) {
            $data = $this->readJson($file);

            preg_match('/kab-(\d+)/', basename($file), $match);
            $provinceId = (string) $match[1];

            $rows = [];

            foreach ($data as $cityCode => $nama) {
                $rows[] = [
                    'id' => str_pad(
                        $provinceId . $cityCode,
                        4,
                        '0',
                        STR_PAD_LEFT
                    ),
                    'id_provinsi' => $provinceId,
                    'nama' => $nama,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $this->insertChunks('cities', $rows);
        }

        /*
        |--------------------------------------------------------------------------
        | DISTRICTS
        |--------------------------------------------------------------------------
        */

        foreach ($kecFiles as $file) {
            $data = $this->readJson($file);

            preg_match(
                '/kec-(\d+)-(\d+)/',
                basename($file),
                $match
            );

            $provinceId = (string) $match[1];
            $cityCode = (string) $match[2];

            $cityId = str_pad(
                $provinceId . $cityCode,
                4,
                '0',
                STR_PAD_LEFT
            );

            $rows = [];

            foreach ($data as $districtCode => $nama) {
                $rows[] = [
                    'id' => str_pad(
                        $provinceId . $cityCode . $districtCode,
                        7,
                        '0',
                        STR_PAD_LEFT
                    ),
                    'id_kota' => $cityId,
                    'nama' => $nama,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $this->insertChunks('districts', $rows);
        }

        /*
        |--------------------------------------------------------------------------
        | URBAN VILLAGES
        |--------------------------------------------------------------------------
        */

        foreach ($kelFiles as $file) {
            $data = $this->readJson($file);

            preg_match(
                '/keldesa-(\d+)-(\d+)-(\d+)/',
                basename($file),
                $match
            );

            $provinceId = (string) $match[1];
            $cityCode = (string) $match[2];
            $districtCode = (string) $match[3];

            $districtId = str_pad(
                $provinceId . $cityCode . $districtCode,
                7,
                '0',
                STR_PAD_LEFT
            );

            $rows = [];

            foreach ($data as $villageCode => $nama) {
                $rows[] = [
                    'id' => $provinceId
                        . $cityCode
                        . $districtCode
                        . str_pad($villageCode, 3, '0', STR_PAD_LEFT),
                    'id_kecamatan' => $districtId,
                    'nama' => $nama,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $this->insertChunks('urban_villages', $rows);
        }

        $this->command?->info(
            'Seeder wilayah Indonesia berhasil.'
        );
    }

    private function readJson(string $path): array
    {
        try {
            $data = json_decode(
                File::get($path),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                "JSON tidak valid: {$path}",
                previous: $exception
            );
        }

        if (!is_array($data)) {
            throw new RuntimeException(
                "Format data JSON tidak valid: {$path}"
            );
        }

        return $data;
    }

    private function insertChunks(string $table, array $rows): void
    {
        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    private function clearTables(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement(
                'TRUNCATE TABLE urban_villages, districts, cities, provinces RESTART IDENTITY CASCADE'
            );

            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            DB::table('urban_villages')->truncate();
            DB::table('districts')->truncate();
            DB::table('cities')->truncate();
            DB::table('provinces')->truncate();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}

<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
class WilayahSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('TRUNCATE TABLE urban_villages, districts, cities, provinces RESTART IDENTITY CASCADE');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('urban_villages')->truncate();
            DB::table('districts')->truncate();
            DB::table('cities')->truncate();
            DB::table('provinces')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
        /*
        |--------------------------------------------------------------------------
        | PROVINCES
        |--------------------------------------------------------------------------
        */
        $provinsiPath = storage_path('app/wilayah-indonesia/provinsi/provinsi.json');
        $provinsi = json_decode(File::get($provinsiPath), true);
        if (!is_array($provinsi)) {
            throw new \Exception("Provinsi JSON tidak valid");
        }
        foreach ($provinsi as $id => $nama) {
            DB::table('provinces')->insert([
                'id' => (string) $id,
                'nama' => $nama,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        /*
        |--------------------------------------------------------------------------
        | CITIES (kabupaten_kota/kab-11.json)
        |--------------------------------------------------------------------------
        */
        $kabFiles = File::glob(storage_path('app/wilayah-indonesia/kabupaten_kota/kab-*.json'));
        foreach ($kabFiles as $file) {
            $data = json_decode(File::get($file), true);
            if (!is_array($data)) continue;
            preg_match('/kab-(\d+)/', basename($file), $match);
            $provinceId = (string) $match[1];
            foreach ($data as $cityCode => $nama) {
                $cityId = str_pad($provinceId . $cityCode, 4, '0', STR_PAD_LEFT);
                DB::table('cities')->insert([
                    'id' => $cityId,
                    'id_provinsi' => $provinceId,
                    'nama' => $nama,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        /*
        |--------------------------------------------------------------------------
        | DISTRICTS (kec-11-01.json)
        |--------------------------------------------------------------------------
        */
        $kecFiles = File::glob(storage_path('app/wilayah-indonesia/kecamatan/kec-*.json'));
        foreach ($kecFiles as $file) {
            $data = json_decode(File::get($file), true);
            if (!is_array($data)) continue;
            preg_match('/kec-(\d+)-(\d+)/', basename($file), $match);
            $provinceId = (string) $match[1];
            $cityCode   = (string) $match[2];
            $cityId = str_pad($provinceId . $cityCode, 4, '0', STR_PAD_LEFT);
            foreach ($data as $districtCode => $nama) {
                $districtId = str_pad($provinceId . $cityCode . $districtCode, 7, '0', STR_PAD_LEFT);
                DB::table('districts')->insert([
                    'id' => $districtId,
                    'id_kota' => $cityId,
                    'nama' => $nama,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        /*
        |--------------------------------------------------------------------------
        | VILLAGES (keldesa-11-01-010.json)
        |--------------------------------------------------------------------------
        */
        $kelFiles = File::glob(storage_path('app/wilayah-indonesia/kelurahan_desa/keldesa-*.json'));
        foreach ($kelFiles as $file) {
            $data = json_decode(File::get($file), true);
            if (!is_array($data)) continue;
            preg_match('/keldesa-(\d+)-(\d+)-(\d+)/', basename($file), $match);
            $provinceId = (string) $match[1];
            $cityCode   = (string) $match[2];
            $districtCode = (string) $match[3];
            $districtId = str_pad($provinceId . $cityCode . $districtCode, 7, '0', STR_PAD_LEFT);
            foreach ($data as $villageCode => $nama) {
                $villageId = $provinceId . $cityCode . $districtCode . str_pad($villageCode, 3, '0', STR_PAD_LEFT);
                DB::table('urban_villages')->insert([
                    'id' => $villageId,
                    'id_kecamatan' => $districtId,
                    'nama' => $nama,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        $this->command->info('Seeder wilayah Indonesia berhasil 🚀');
    }
}

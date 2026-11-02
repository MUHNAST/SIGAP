<?php
namespace Database\Factories;
use App\Models\City;
use App\Models\District;
use App\Models\Position;
use App\Models\Province;
use App\Models\Rank;
use App\Models\UrbanVillage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        $province = Province::inRandomOrder()->first();
        $city = City::where('id_provinsi', $province?->id)->inRandomOrder()->first();
        $district = District::where('id_kota', $city?->id)->inRandomOrder()->first();
        $village = UrbanVillage::where('id_kecamatan', $district?->id)->inRandomOrder()->first();
        // Umur pegawai 20 - 57 tahun
        $tanggalLahir = Carbon::instance(
            fake()->dateTimeBetween('-57 years', '-20 years')
        );
        $tanggalMasuk = $tanggalLahir
            ->copy()
            ->addYears(rand(18,25))
            ->addMonths(rand(0,11))
            ->addDays(rand(0,27));
        if ($tanggalMasuk->greaterThan(now())) {
            $tanggalMasuk = now()->subMonths(rand(1,12));
        }
        // TMT Pangkat
        // Lama bekerja (0,4,8,12,16,20,24,28)
        $masaKerjaPangkat = fake()->randomElement([0, 4, 8, 12, 16, 20, 24, 28]);
        $tmtPangkatLama = $tanggalMasuk
            ->copy()
            ->addYears($masaKerjaPangkat);
        if ($tmtPangkatLama->greaterThan(now())) {
            $tmtPangkatLama = $tanggalMasuk;
        }
        $tmtPangkatBaru = $tmtPangkatLama->copy()->addYears(4);
        $masaKerjaKgb = fake()->randomElement([
            0,2,4,6,8,10,12,14,16,18,20,22,24,26,28
        ]);
        $tmtKgbLama = $tanggalMasuk
            ->copy()
            ->addYears($masaKerjaKgb);
        if ($tmtKgbLama->greaterThan(now())) {
            $tmtKgbLama = $tanggalMasuk;
        }
        $tmtKgbBaru = $tmtKgbLama->copy()->addYears(2);
        $tanggalPensiun = $tanggalLahir->copy()->addYears(58);
        return [
            'nik' => fake()->unique()->numerify('################'),
            'nip' => fake()->unique()->numerify('##################'),
            'id_user' => User::inRandomOrder()->value('id'),
            'id_pangkat' => Rank::inRandomOrder()->value('id'),
            'id_jabatan' => Position::inRandomOrder()->value('id'),
            // 'id_unit_kerja' => Department::inRandomOrder()->value('id'),
            // 'id_gaji' => Salary::inRandomOrder()->value('id'),
            'nama' => fake()->name(),
            'pendidikan' => fake()->randomElement([
                'SD/Sederajat',
                'SMP/Sederajat',
                'SMA/SMK/MA/Sederajat',
                'D1',
                'D2',
                'D3',
                'D4',
                'S1',
                'S2',
                'S3',
            ]),
            'tempat_lahir' => $city?->id,
            'tanggal_lahir' => $tanggalLahir,
            'jenis_kelamin' => fake()->randomElement([
                'Laki-laki',
                'Perempuan',
            ]),
            'agama' => fake()->randomElement([
                'Islam',
                'Kristen Protestan',
                'Kristen Katolik',
                'Budha',
                'Hindu',
                'Konghucu',
            ]),
            'alamat' => fake()->address(),
            'id_provinsi' => $province?->id,
            'id_kota' => $city?->id,
            'id_kecamatan' => $district?->id,
            'id_kelurahan' => $village?->id,
            'status_pegawai' => fake()->randomElement([
                'PNS',
                'PPPK',
                'HONORER',
            ]),
            'status_nikah' => fake()->randomElement([
                'Belum Menikah',
                'Menikah',
                'Cerai Hidup',
                'Cerai Mati',
            ]),
            'tanggal_masuk' => $tanggalMasuk,
            // =========================
            // DATA KEPEGAWAIAN
            // =========================
            'tmt_pangkat_lama' => $tmtPangkatLama,
            'tmt_pangkat_baru' => $tmtPangkatBaru,
            'tmt_kgb_lama' => $tmtKgbLama,
            'tmt_kgb_baru' => $tmtKgbBaru,
            'tmt_pensiun' => $tanggalPensiun,
            'status' => 'Aktif',
            'foto' => 'images/default-user.png',
        ];
    }
}

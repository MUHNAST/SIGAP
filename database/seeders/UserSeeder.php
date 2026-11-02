<?php
namespace Database\Seeders;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class UserSeeder extends Seeder
{
    /**
     * Jalankan seeder.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN
        |--------------------------------------------------------------------------
        */
        User::updateOrCreate(
            [
                'username' => 'superadmin',
            ],
            [
                'kode_unit_kerja' => 'KC-PT',
                'password' => Hash::make('123456789'),
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ]
        );
        /*
        |--------------------------------------------------------------------------
        | ADMIN KELURAHAN PONCOL
        |--------------------------------------------------------------------------
        */
        User::updateOrCreate(
            [
                'username' => 'admin_poncol',
            ],
            [
                'kode_unit_kerja' => 'K-PNC',
                'password' => Hash::make('123456789'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
    }
}

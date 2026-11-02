<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            WilayahSeeder::class,
            PppkSeeder::class,
            RankSeeder::class,
            PositionSeeder::class,
            DepartmentSeeder::class,
            EducationLevelSeeder::class,
            SalarySeeder::class,
            UserSeeder::class,
            EmployeeSeeder::class,
            PermissionSeeder::class
        ]);
    }
}

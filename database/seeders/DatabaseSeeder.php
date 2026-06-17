<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Menggunakan updateOrCreate agar data user yang sudah ada tidak terhapus atau terduplikasi
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin MTS',
                'password' => bcrypt('adminmts123'),
                'role' => 'admin',
                'nis_nik' => null, // Sesuai dengan data di database Anda
            ]
        );

        User::updateOrCreate(
            ['email' => 'guru@guru.com'],
            [
                'name' => 'Guru MTS',
                'password' => bcrypt('gurumts123'),
                'role' => 'guru',
                'nis_nik' => '89879811', // Sesuai dengan data di database Anda
                'tahun_ajaran' => '2016/2030', // Sesuai dengan data di database Anda
            ]
        );

        User::updateOrCreate(
            ['email' => 'siswa@siswa.com'],
            [
                'name' => 'Siswa MTS',
                'password' => bcrypt('siswamts123'),
                'role' => 'siswa',
                'nis_nik' => null, // Sesuai dengan data di database Anda
                'class' => null, // Sesuai dengan data di database Anda
                'tahun_ajaran' => null, // Sesuai dengan data di database Anda
            ]
        );
    }
}

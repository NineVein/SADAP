<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Untuk instalasi baru. Isi ADMIN_EMAIL dan ADMIN_PASSWORD di .env,
 * lalu jalankan: php artisan db:seed --class=AdminSeeder
 * Admin yang sudah ada tidak diubah.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD belum diisi di .env, AdminSeeder dilewati.');

            return;
        }

        User::firstOrCreate(
            ['email' => strtolower($email)],
            [
                'name' => 'Administrator',
                'password' => $password,
                'role_id' => Role::where('name', Role::ADMIN)->value('id'),
                'is_active' => true,
            ]
        );
    }
}

<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Development admin tidak dibuat di luar environment local/testing.');

            return;
        }

        /** @var array{name: mixed, email: mixed, password: mixed} $admin */
        $admin = config('development.admin');

        if (! is_string($admin['name']) || ! is_string($admin['email']) || ! is_string($admin['password'])) {
            $this->command?->warn('DEV_ADMIN_NAME, DEV_ADMIN_EMAIL, dan DEV_ADMIN_PASSWORD wajib diisi.');

            return;
        }

        User::query()->updateOrCreate(
            ['email' => mb_strtolower(trim($admin['email']))],
            [
                'name' => trim($admin['name']),
                'password' => $admin['password'],
                'role' => UserRole::Admin,
                'is_active' => true,
            ],
        );

        $this->command?->info('Development admin berhasil disiapkan.');
    }
}

<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class ProductionAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('production')) {
            throw new RuntimeException('ProductionAdminSeeder hanya boleh dijalankan dengan APP_ENV=production.');
        }

        /** @var array{enabled: mixed, name: mixed, email: mixed, whatsapp: mixed, password: mixed} $admin */
        $admin = config('operations.initial_admin');
        if ($admin['enabled'] !== true) {
            throw new RuntimeException('Set INITIAL_ADMIN_ENABLED=true hanya selama bootstrap admin production.');
        }

        $data = Validator::make($admin, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'whatsapp' => ['required', 'regex:/^62[0-9]{8,13}$/'],
            'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()],
        ])->validate();

        $email = mb_strtolower(trim((string) $data['email']));
        if (User::query()->where('email', $email)->exists()) {
            $this->command->warn('Admin production sudah tersedia; password tidak diubah.');

            return;
        }

        $whatsapp = trim((string) $data['whatsapp']);
        $user = User::query()->create([
            'name' => trim((string) $data['name']),
            'email' => $email,
            'whatsapp' => $whatsapp,
            'whatsapp_hash' => hash('sha256', $whatsapp),
            'password' => (string) $data['password'],
            'role' => UserRole::Admin,
            'is_active' => true,
            'activated_at' => now(),
        ]);

        if (! Hash::check((string) $data['password'], $user->password)) {
            throw new RuntimeException('Password admin production gagal diverifikasi.');
        }

        $this->command->info('Admin production berhasil dibuat. Hapus INITIAL_ADMIN_PASSWORD dan nonaktifkan INITIAL_ADMIN_ENABLED.');
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductionCheck extends Command
{
    protected $signature = 'app:production-check';

    protected $description = 'Validasi konfigurasi minimum sebelum aplikasi digunakan di production';

    public function handle(): int
    {
        $checks = [
            ['APP_ENV production', app()->environment('production')],
            ['APP_DEBUG nonaktif', config('app.debug') === false],
            ['APP_URL HTTPS', str_starts_with((string) config('app.url'), 'https://')],
            ['APP_KEY tersedia', filled(config('app.key'))],
            ['Timezone Asia/Makassar', config('app.timezone') === 'Asia/Makassar'],
            ['Queue bukan sync', config('queue.default') !== 'sync'],
            ['Session secure cookie', config('session.secure') === true],
            ['API client key tersedia', filled(config('api.client_key'))],
            ['Token Fonnte tersedia', filled(config('services.fonnte.token'))],
            ['Nomor resepsionis tersedia', filled(config('services.fonnte.reception_number'))],
        ];

        try {
            DB::select('select 1');
            $checks[] = ['Database terhubung', true];
        } catch (Throwable) {
            $checks[] = ['Database terhubung', false];
        }

        try {
            $probe = '.operations-write-probe';
            $written = Storage::disk((string) config('filesystems.default'))->put($probe, 'ok');
            if ($written) {
                Storage::disk((string) config('filesystems.default'))->delete($probe);
            }
            $checks[] = ['Private storage dapat ditulis', $written];
        } catch (Throwable) {
            $checks[] = ['Private storage dapat ditulis', false];
        }

        $failed = false;
        foreach ($checks as [$label, $passed]) {
            $passed ? $this->components->info($label) : $this->components->error($label);
            $failed = $failed || ! $passed;
        }

        if (config('operations.initial_admin.enabled')) {
            $this->components->warn('Bootstrap admin masih aktif. Nonaktifkan dan hapus password setelah seeding.');
            $failed = true;
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class SeedPerformanceVisits extends Command
{
    protected $signature = 'performance:seed-visits {--count=100000}';

    protected $description = 'Buat data kunjungan sintetis untuk load test staging';

    public function handle(): int
    {
        if (! app()->environment(['local', 'staging'])) {
            $this->components->error('Perintah ini hanya boleh dijalankan pada local/staging.');

            return self::FAILURE;
        }

        $count = max(1, min(100000, (int) $this->option('count')));
        $employeeId = Employee::query()->where('is_active', true)->value('id');
        if (! is_int($employeeId)) {
            $this->components->error('Buat minimal satu pegawai aktif sebelum menyiapkan data performa.');

            return self::FAILURE;
        }

        $start = (int) DB::table('visits')->where('visit_number', 'like', 'PERF-%')->count();
        $now = now();
        for ($offset = 0; $offset < $count; $offset += 500) {
            $rows = [];
            $limit = min(500, $count - $offset);
            for ($index = 0; $index < $limit; $index++) {
                $number = $start + $offset + $index + 1;
                $status = match ($number % 3) {
                    0 => 'accepted', 1 => 'rejected', default => 'pending'
                };
                $key = hash('sha256', 'performance-'.$number);
                $rows[] = [
                    'visit_number' => sprintf('PERF-%010d', $number),
                    'employee_id' => $employeeId,
                    'guest_name' => 'Tamu Performa '.$number,
                    'address' => 'Data sintetis staging',
                    'guest_whatsapp' => Crypt::encryptString('6280000000000'),
                    'visit_purpose' => 'Pengujian performa aplikasi',
                    'photo_path' => 'performance/placeholder.jpg',
                    'photo_mime_type' => 'image/jpeg',
                    'status' => $status,
                    'decision_reason' => $status === 'rejected' ? 'Data sintetis' : null,
                    'decided_at' => $status === 'pending' ? null : $now,
                    'arrived_at' => $now->copy()->subMinutes($number % 525600),
                    'idempotency_key_hash' => $key,
                    'request_fingerprint' => $key,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('visits')->insert($rows);
            $this->output->write("\rData sintetis: ".min($offset + $limit, $count)."/{$count}");
        }

        $this->newLine();

        return self::SUCCESS;
    }
}

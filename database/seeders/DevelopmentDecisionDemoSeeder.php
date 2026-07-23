<?php

namespace Database\Seeders;

use App\Enums\VisitStatus;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Visit;
use App\Models\WorkUnit;
use App\Services\DecisionLinkService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class DevelopmentDecisionDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('Seeder demo keputusan hanya boleh dijalankan pada environment local.');
        }

        $workUnit = WorkUnit::query()->firstOrCreate(
            ['name' => 'Unit Demo Pelayanan'],
            ['is_active' => true, 'sort_order' => 999],
        );
        $position = Position::query()->firstOrCreate(
            ['name' => 'Pegawai Penerima Demo'],
            ['is_active' => true, 'sort_order' => 999],
        );
        $employee = Employee::query()->firstOrCreate(
            ['employee_no' => 'DEMO-KEPUTUSAN'],
            [
                'work_unit_id' => $workUnit->id,
                'position_id' => $position->id,
                'name' => 'Pegawai Demo PTA Manado',
                'notification_contact' => '6281234567890',
                'is_active' => true,
            ],
        );

        if (! $employee->is_active || $employee->notification_contact === null) {
            $employee->update([
                'work_unit_id' => $workUnit->id,
                'position_id' => $position->id,
                'notification_contact' => '6281234567890',
                'is_active' => true,
            ]);
        }

        $photoPath = 'visits/demo/'.Str::uuid().'.png';
        $photo = $this->demoPhoto();
        if (! Storage::disk('local')->put($photoPath, $photo)) {
            throw new RuntimeException('Foto demo tidak dapat disimpan.');
        }

        $visit = Visit::query()->create([
            'visit_number' => 'DEMO-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4)),
            'employee_id' => $employee->id,
            'guest_name' => 'Tamu Demo PTA Manado',
            'address' => 'Kota Manado, Sulawesi Utara',
            'guest_whatsapp' => '6281234567891',
            'visit_purpose' => 'Menguji tampilan dan alur keputusan kunjungan.',
            'photo_path' => $photoPath,
            'photo_mime_type' => 'image/png',
            'status' => VisitStatus::Pending,
            'arrived_at' => now(),
            'idempotency_key_hash' => hash('sha256', (string) Str::uuid()),
            'request_fingerprint' => hash('sha256', Str::random(64)),
        ]);

        $link = app(DecisionLinkService::class)->issue($visit);

        $this->command?->newLine();
        $this->command?->info('Data demo keputusan berhasil dibuat.');
        $this->command?->line('Nomor kunjungan: '.$visit->visit_number);
        $this->command?->line('URL keputusan: '.$link->url);
        $this->command?->warn('URL bersifat sekali pakai dan tidak aktif setelah keputusan disimpan.');
    }

    private function demoPhoto(): string
    {
        $image = imagecreatetruecolor(1200, 800);
        $background = imagecolorallocate($image, 8, 47, 73);
        $accent = imagecolorallocate($image, 56, 189, 248);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $background);
        imagefilledellipse($image, 600, 285, 220, 220, $accent);
        imagefilledrectangle($image, 420, 430, 780, 650, $accent);
        imagestring($image, 5, 491, 700, 'FOTO TAMU DEMO', $white);

        ob_start();
        imagepng($image);
        $contents = ob_get_clean();

        if (! is_string($contents)) {
            throw new RuntimeException('Gambar demo tidak dapat dibuat.');
        }

        return $contents;
    }
}

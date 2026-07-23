<?php

namespace App\Console\Commands;

use App\Contracts\WhatsAppGateway;
use App\Enums\VisitStatus;
use App\Models\Employee;
use App\Models\Visit;
use App\Services\DecisionLinkService;
use App\Services\VisitWhatsAppMessageFactory;
use App\Support\WhatsAppNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SendFonnteDecisionLinkTest extends Command
{
    protected $signature = 'fonnte:test-decision-link {--send : Konfirmasi pengiriman WhatsApp nyata}';

    protected $description = 'Kirim tautan keputusan demo ke Ketua dan nomor reception melalui Fonnte';

    public function handle(
        WhatsAppGateway $gateway,
        DecisionLinkService $links,
        VisitWhatsAppMessageFactory $messages,
    ): int {
        if (! app()->environment('local')) {
            $this->error('Perintah uji Fonnte hanya boleh dijalankan pada environment local.');

            return self::FAILURE;
        }

        if (! $this->option('send')) {
            $this->error('Tambahkan --send untuk mengonfirmasi pengiriman WhatsApp nyata.');

            return self::FAILURE;
        }

        $employee = Employee::query()
            ->with('position')
            ->where(function ($query): void {
                $query->where('name', 'like', '%Ketua%')
                    ->orWhereHas('position', fn ($query) => $query->where('name', 'like', '%Ketua%'));
            })
            ->orderBy('id')
            ->first();
        $employeeTarget = WhatsAppNumber::normalize($employee?->notification_contact);
        $receptionTarget = WhatsAppNumber::normalize(config('services.fonnte.reception_number'));
        if ($employee === null || $employeeTarget === null) {
            $this->error('Pegawai dengan nama/jabatan Ketua dan nomor WhatsApp valid tidak ditemukan.');

            return self::FAILURE;
        }
        if ($receptionTarget === null) {
            $this->error('RECEPTION_WHATSAPP_NUMBER belum valid.');

            return self::FAILURE;
        }

        $photoPath = 'visits/demo/'.Str::uuid().'.png';
        Storage::disk('local')->put($photoPath, $this->demoPhoto());
        $visit = Visit::query()->create([
            'visit_number' => 'TEST-WA-'.now()->format('Ymd-His'),
            'employee_id' => $employee->id,
            'guest_name' => 'Tamu Uji Fonnte',
            'address' => 'PTA Manado (data uji)',
            'guest_whatsapp' => '6281234567891',
            'visit_purpose' => 'Menguji pengiriman tautan keputusan melalui Fonnte API.',
            'photo_path' => $photoPath,
            'photo_mime_type' => 'image/png',
            'status' => VisitStatus::Pending,
            'arrived_at' => now(),
            'idempotency_key_hash' => hash('sha256', (string) Str::uuid()),
            'request_fingerprint' => hash('sha256', Str::random(64)),
        ]);
        $link = $links->issue($visit);
        $message = "*UJI COBA SISTEM — BUKAN TAMU SEBENARNYA*\n\n"
            .$messages->employeeArrival($visit, $link->url);

        $failed = false;
        foreach ([
            'Ketua' => $employeeTarget,
            'Reception' => $receptionTarget,
        ] as $label => $target) {
            try {
                $result = $gateway->send($target, $message);
                $this->info("{$label}: diterima Fonnte (request ID {$result->requestId}, message ID {$result->messageId}).");
            } catch (Throwable) {
                $this->error("{$label}: pengiriman gagal dengan error tersanitasi.");
                $failed = true;
            }
        }

        $this->line('Nomor kunjungan uji: '.$visit->visit_number);
        $this->warn('Tautan memakai APP_URL lokal dan tidak dapat dibuka dari ponsel sebelum aplikasi memiliki URL publik.');

        return $failed ? self::FAILURE : self::SUCCESS;
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
        imagestring($image, 5, 475, 700, 'UJI FONNTE PTA', $white);
        ob_start();
        imagepng($image);
        $contents = ob_get_clean();

        return $contents;
    }
}

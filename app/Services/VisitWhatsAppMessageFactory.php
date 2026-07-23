<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Visit;
use DomainException;

class VisitWhatsAppMessageFactory
{
    public function employeeArrival(Visit $visit, string $decisionUrl): string
    {
        return "*Pemberitahuan Tamu PTA Manado*\n\n"
            ."Nama: {$visit->guest_name}\n"
            ."Alamat: {$visit->address}\n"
            ."Maksud: {$visit->visit_purpose}\n"
            .'Waktu: '.$visit->arrived_at->timezone('Asia/Makassar')->format('d-m-Y H:i')." WITA\n\n"
            ."Lihat detail dan berikan keputusan:\n{$decisionUrl}\n\n"
            .'Tautan bersifat rahasia dan hanya dapat digunakan sampai keputusan dibuat.';
    }

    public function receptionDecision(Visit $visit, NotificationType $type): string
    {
        return match ($type) {
            NotificationType::ReceptionAccepted => "*Keputusan Kunjungan PTA Manado*\n\n"
                ."Tamu {$visit->guest_name} telah diterima.\n"
                .'Silakan arahkan tamu sesuai alur pelayanan yang berlaku.',
            NotificationType::ReceptionRejected => "*Keputusan Kunjungan PTA Manado*\n\n"
                ."Tamu {$visit->guest_name} tidak dapat diterima.\n"
                ."Alasan: {$visit->decision_reason}",
            default => throw new DomainException('Jenis notifikasi petugas tidak valid.'),
        };
    }
}

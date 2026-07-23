<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Visit;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PurgeExpiredVisits extends Command
{
    protected $signature = 'visits:purge-expired {--dry-run : Hitung tanpa menghapus} {--batch= : Ukuran batch}';

    protected $description = 'Hapus data kunjungan yang melewati retensi tiga tahun kalender.';

    public function handle(): int
    {
        $timezone = (string) config('retention.timezone', 'Asia/Makassar');
        $years = max(1, (int) config('retention.visit_years', 3));
        $batch = max(1, min(1000, (int) ($this->option('batch') ?: config('retention.batch_size', 100))));
        $retainFrom = CarbonImmutable::now($timezone)->startOfYear()->subYears($years - 1);
        $query = Visit::query()->where('arrived_at', '<', $retainFrom);
        $eligible = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info("Dry-run: {$eligible} kunjungan sebelum {$retainFrom->format('Y-m-d')} eligible dihapus.");

            return self::SUCCESS;
        }

        $lock = Cache::lock('retention:visits', 3600);
        if (! $lock->get()) {
            $this->error('Cleanup retensi lain sedang berjalan.');

            return self::FAILURE;
        }

        $deleted = 0;
        $failed = 0;
        $runId = (string) Str::uuid();
        try {
            $query->orderBy('id')->chunkById($batch, function ($visits) use (&$deleted, &$failed): void {
                foreach ($visits as $visit) {
                    if ($this->purge($visit)) {
                        $deleted++;
                    } else {
                        $failed++;
                    }
                }
            });
        } finally {
            $lock->release();
        }

        AuditLog::query()->create([
            'actor_type' => 'scheduler',
            'action' => 'retention.visits_purged',
            'auditable_type' => 'retention_run',
            'auditable_id' => 0,
            'metadata' => ['cutoff_date' => $retainFrom->format('Y-m-d'), 'eligible' => $eligible, 'deleted' => $deleted, 'failed' => $failed],
            'request_id' => $runId,
        ]);
        $this->info("Cleanup selesai: {$deleted} dihapus, {$failed} gagal.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function purge(Visit $visit): bool
    {
        $disk = Storage::disk('local');
        $original = $visit->photo_path;
        $quarantine = 'retention-trash/'.Str::uuid().'.bin';
        $moved = false;
        try {
            if ($disk->exists($original)) {
                if (! $disk->move($original, $quarantine)) {
                    return false;
                }
                $moved = true;
            }
            DB::transaction(function () use ($visit): void {
                AuditLog::query()->where('auditable_type', Visit::class)->where('auditable_id', $visit->id)->delete();
                Visit::query()->whereKey($visit->id)->delete();
            });
            if ($moved && ! $disk->delete($quarantine)) {
                return false;
            }

            return true;
        } catch (Throwable) {
            if ($moved && $disk->exists($quarantine) && ! $disk->exists($original)) {
                $disk->move($quarantine, $original);
            }

            return false;
        }
    }
}

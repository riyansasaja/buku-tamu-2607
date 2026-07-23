<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class OperationsStatus extends Command
{
    protected $signature = 'operations:status {--json : Keluarkan JSON untuk monitoring}';

    protected $description = 'Periksa heartbeat scheduler, backlog queue, dan failed jobs';

    public function handle(): int
    {
        try {
            $heartbeat = Cache::get((string) config('operations.scheduler_heartbeat_key'));
            $lastSeen = is_string($heartbeat) ? CarbonImmutable::parse($heartbeat) : null;
            $schedulerOk = $lastSeen !== null && $lastSeen->greaterThan(now()->subMinutes((int) config('operations.scheduler_stale_after_minutes')));
            $queued = (int) DB::table('jobs')->count();
            $failed = (int) DB::table('failed_jobs')->count();
            $queueOk = $queued < (int) config('operations.queue_backlog_warning')
                && $failed < (int) config('operations.failed_jobs_warning');
        } catch (Throwable) {
            $schedulerOk = false;
            $queueOk = false;
            $queued = -1;
            $failed = -1;
        }

        $status = [
            'status' => $schedulerOk && $queueOk ? 'ok' : 'degraded',
            'checks' => [
                'scheduler' => $schedulerOk ? 'ok' : 'stale',
                'queue' => $queueOk ? 'ok' : 'warning',
            ],
            'metrics' => ['queued_jobs' => $queued, 'failed_jobs' => $failed],
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($status, JSON_THROW_ON_ERROR));
        } else {
            $this->table(['Check', 'Status'], [
                ['Scheduler', $status['checks']['scheduler']],
                ['Queue', $status['checks']['queue']],
                ['Queued jobs', $queued],
                ['Failed jobs', $failed],
            ]);
        }

        return $status['status'] === 'ok' ? self::SUCCESS : self::FAILURE;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class OperationsHeartbeat extends Command
{
    protected $signature = 'operations:heartbeat';

    protected $description = 'Catat heartbeat scheduler untuk monitoring';

    public function handle(): int
    {
        Cache::put((string) config('operations.scheduler_heartbeat_key'), now()->toIso8601String(), now()->addDay());

        return self::SUCCESS;
    }
}

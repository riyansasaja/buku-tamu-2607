<?php

namespace App\Providers;

use App\Contracts\WhatsAppGateway;
use App\Enums\NotificationType;
use App\Enums\VisitStatus;
use App\Events\VisitDecisionRecorded;
use App\Events\VisitRecorded;
use App\Jobs\SendVisitWhatsApp;
use App\Services\FonnteWhatsAppGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WhatsAppGateway::class, FonnteWhatsAppGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api-employees', fn (Request $request): Limit => Limit::perMinute((int) config('api.rate_limits.employees'))
            ->by('employees|'.hash('sha256', (string) $request->header('X-Client-Key')).'|'.$request->ip()));

        RateLimiter::for('api-visits', fn (Request $request): Limit => Limit::perMinute((int) config('api.rate_limits.visits'))
            ->by('visits|'.hash('sha256', (string) $request->header('X-Client-Key')).'|'.$request->ip()));

        Event::listen(VisitRecorded::class, fn (VisitRecorded $event) => SendVisitWhatsApp::dispatch(
            $event->visitId,
            NotificationType::EmployeeArrival,
        ));

        Event::listen(VisitDecisionRecorded::class, function (VisitDecisionRecorded $event): void {
            $type = $event->status === VisitStatus::Accepted
                ? NotificationType::ReceptionAccepted
                : NotificationType::ReceptionRejected;
            SendVisitWhatsApp::dispatch($event->visitId, $type);
        });
    }
}

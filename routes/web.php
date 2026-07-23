<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\VisitController;
use App\Http\Controllers\Admin\VisitReportController;
use App\Http\Controllers\Admin\WorkUnitController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DecisionPageController;
use App\Http\Controllers\StoreVisitDecisionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/decisions/{token}', DecisionPageController::class)->where('token', '[A-Za-z0-9]{64}')->name('decisions.show');
Route::post('/decisions/{token}', StoreVisitDecisionController::class)->where('token', '[A-Za-z0-9]{64}')->name('decisions.store');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'admin.active'])->group(function (): void {
    Route::get('/admin', DashboardController::class)->name('admin.dashboard');
    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::resource('employees', EmployeeController::class)->except(['show', 'destroy']);
        Route::patch('employees/{employee}/status', [EmployeeController::class, 'status'])->name('employees.status');

        Route::resource('work-units', WorkUnitController::class)->except(['show', 'destroy'])->parameters(['work-units' => 'work_unit']);
        Route::patch('work-units/{work_unit}/status', [WorkUnitController::class, 'status'])->name('work-units.status');

        Route::resource('positions', PositionController::class)->except(['show', 'destroy']);
        Route::patch('positions/{position}/status', [PositionController::class, 'status'])->name('positions.status');
        Route::resource('visits', VisitController::class)->only(['index', 'show']);
        Route::get('reports/visits.pdf', VisitReportController::class)->name('reports.visits.pdf');
    });
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

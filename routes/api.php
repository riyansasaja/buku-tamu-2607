<?php

use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\VisitController;
use App\Http\Controllers\Api\V1\VisitPhotoController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware('api.request-id')->group(function (): void {
    Route::middleware('api.client')->group(function (): void {
        Route::get('employees', [EmployeeController::class, 'index'])->middleware('throttle:api-employees')->name('employees.index');
        Route::post('visits', [VisitController::class, 'store'])->middleware('throttle:api-visits')->name('visits.store');
    });

    Route::get('visits/{visit}/photo', VisitPhotoController::class)->middleware('signed')->name('visits.photo');
});

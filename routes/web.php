<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\ClientArchiveController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientProgressReportController;
use App\Http\Controllers\ClinicalHoursLinkController;
use App\Http\Controllers\ClinicalLogFormController;
use App\Http\Controllers\ClinicalLogFormPdfController;
use App\Http\Controllers\ContentItemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OfflineActivityController;
use App\Http\Controllers\PlaySessionController;
use App\Http\Controllers\PlaySessionStickerController;
use App\Http\Controllers\PlaySessionSyncController;
use App\Http\Controllers\TherapyGoalController;
use App\Http\Controllers\TherapyGoalStatusController;
use App\Http\Controllers\TrialController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// The offline play screen is a shell cached by the service worker; its data lives on the device.
Route::inertia('offline', 'offline/Index')->name('offline');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('clinical-hours', ClinicalLogFormController::class)
        ->except('show')
        ->parameters(['clinical-hours' => 'clinicalLogForm']);

    Route::get('clinical-hours/{clinicalLogForm}/pdf', [ClinicalLogFormPdfController::class, 'show'])
        ->name('clinical-hours.pdf');

    Route::resource('clients', ClientController::class);
    Route::patch('clients/{client}/archive', ClientArchiveController::class)->name('clients.archive');
    Route::post('clients/{client}/activities', [AssignmentController::class, 'store'])->name('clients.activities.store');
    Route::delete('clients/{client}/activities/{activity}', [AssignmentController::class, 'destroy'])->name('clients.activities.destroy');

    Route::get('clients/{client}/activities/{activity}/offline', OfflineActivityController::class)->name('clients.activities.offline');
    Route::get('clients/{client}/progress-report', ClientProgressReportController::class)->name('clients.progress-report');
    Route::get('clients/{client}/clinical-hours/create', [ClinicalHoursLinkController::class, 'create'])->name('clients.clinical-hours.create');
    Route::post('clients/{client}/clinical-hours', [ClinicalHoursLinkController::class, 'store'])->name('clients.clinical-hours.store');

    Route::post('clients/{client}/goals', [TherapyGoalController::class, 'store'])->name('clients.goals.store');
    Route::put('goals/{therapyGoal}', [TherapyGoalController::class, 'update'])->name('goals.update');
    Route::delete('goals/{therapyGoal}', [TherapyGoalController::class, 'destroy'])->name('goals.destroy');
    Route::patch('goals/{therapyGoal}/status', TherapyGoalStatusController::class)->name('goals.status');

    Route::resource('activities', ActivityController::class)->except('show');

    Route::resource('content-items', ContentItemController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['content-items' => 'contentItem']);

    Route::post('play-sessions', [PlaySessionController::class, 'store'])->name('play-sessions.store');
    Route::post('play-sessions/sync', PlaySessionSyncController::class)->name('play-sessions.sync');
    Route::get('play-sessions/{playSession}', [PlaySessionController::class, 'show'])->name('play-sessions.show');
    Route::patch('play-sessions/{playSession}', [PlaySessionController::class, 'update'])->name('play-sessions.update');
    Route::patch('play-sessions/{playSession}/finish', [PlaySessionController::class, 'finish'])->name('play-sessions.finish');
    Route::get('play-sessions/{playSession}/summary', [PlaySessionController::class, 'summary'])->name('play-sessions.summary');
    Route::post('play-sessions/{playSession}/trials', [TrialController::class, 'store'])->name('play-sessions.trials.store');
    Route::put('play-sessions/{playSession}/stickers', PlaySessionStickerController::class)->name('play-sessions.stickers.update');
});

require __DIR__.'/settings.php';

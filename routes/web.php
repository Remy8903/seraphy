<?php

use App\Http\Controllers\ClinicalLogFormController;
use App\Http\Controllers\ClinicalLogFormPdfController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('clinical-hours', ClinicalLogFormController::class)
        ->except('show')
        ->parameters(['clinical-hours' => 'clinicalLogForm']);

    Route::get('clinical-hours/{clinicalLogForm}/pdf', [ClinicalLogFormPdfController::class, 'show'])
        ->name('clinical-hours.pdf');
});

require __DIR__.'/settings.php';

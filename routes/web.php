<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JobApplicationController;

Route::get('/', [JobApplicationController::class, 'index'])->name('job-application.form');
Route::post('/submit', [JobApplicationController::class, 'store'])->name('job-application.store');
Route::get('/success', [JobApplicationController::class, 'success'])->name('job-application.success');
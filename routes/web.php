<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DebtController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/debt-tracker', [DebtController::class, 'index'])->name('debt-tracker.index');
    Route::post('/debt-tracker', [DebtController::class, 'store'])->name('debt-tracker.store');
    Route::post('/debt-tracker/{debt}/toggle/{monthIndex}', [DebtController::class, 'togglePayment'])->name('debt-tracker.toggle');
    Route::delete('/debt-tracker/{debt}', [DebtController::class, 'destroy'])->name('debt-tracker.destroy');
});

require __DIR__.'/auth.php';

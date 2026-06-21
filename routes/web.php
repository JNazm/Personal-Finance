<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\CommitmentController;
use App\Http\Controllers\OtherDebtController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/salary', [DashboardController::class, 'updateSalary'])->name('dashboard.salary');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/my-debt', [DebtController::class, 'index'])->name('my-debt.index');
    Route::post('/my-debt', [DebtController::class, 'store'])->name('my-debt.store');
    Route::post('/my-debt/{debt}/toggle/{monthIndex}', [DebtController::class, 'togglePayment'])->name('my-debt.toggle');
    Route::delete('/my-debt/{debt}', [DebtController::class, 'destroy'])->name('my-debt.destroy');

    Route::get('/debt-from-others', [OtherDebtController::class, 'index'])->name('other-debts.index');
    Route::post('/debt-from-others', [OtherDebtController::class, 'store'])->name('other-debts.store');
    Route::post('/debt-from-others/{otherDebt}/toggle/{monthIndex}', [OtherDebtController::class, 'togglePayment'])->name('other-debts.toggle');
    Route::delete('/debt-from-others/{otherDebt}', [OtherDebtController::class, 'destroy'])->name('other-debts.destroy');

    Route::get('/commitments', [CommitmentController::class, 'index'])->name('commitments.index');
    Route::post('/commitments', [CommitmentController::class, 'store'])->name('commitments.store');
    Route::post('/commitments/{commitment}/toggle/{payment}', [CommitmentController::class, 'togglePayment'])->name('commitments.toggle');
    Route::delete('/commitments/{commitment}', [CommitmentController::class, 'destroy'])->name('commitments.destroy');
});

require __DIR__.'/auth.php';

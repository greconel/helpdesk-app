<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');

require __DIR__.'/auth.php';

Route::middleware(['auth'])->group(function () {
    // Breeze profile routes
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');

    // Jouw eigen routes
    Route::get('/overview', [DashboardController::class, 'overview'])->name('overview');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/agents', [DashboardController::class, 'agentsBoard'])->name('agents.board');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::patch('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::patch('/tickets/{ticket}/move', [TicketController::class, 'move'])->name('tickets.move');
});
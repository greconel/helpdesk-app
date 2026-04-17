<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TimeLogController;

Route::get('/', function () {
    return redirect()->route('login');
});

require __DIR__.'/auth.php';

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/overview', [DashboardController::class, 'overview'])->name('overview');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/agents', [DashboardController::class, 'agentsBoard'])->name('agents.board');

    // Agent ticket aanmaken — VOOR de {ticket} wildcard route!
    Route::get('/tickets/agent/create', [TicketController::class, 'agentCreate'])->name('tickets.agent.create');
    Route::post('/tickets/agent', [TicketController::class, 'agentStore'])->name('tickets.agent.store');
    Route::get('/api/customers/search', [TicketController::class, 'searchCustomers'])->name('customers.search');

    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::patch('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::patch('/tickets/{ticket}/move', [TicketController::class, 'move'])->name('tickets.move');
    Route::post('/tickets/{ticket}/timelogs', [TimeLogController::class, 'store'])->name('timelogs.store');

    Route::post('/tickets/{ticket}/reply', [App\Http\Controllers\TicketReplyController::class, 'store'])->name('tickets.reply');
    Route::get('/corrections/export', [App\Http\Controllers\CorrectionExportController::class, 'export'])
    ->name('corrections.export');

    Route::patch('/corrections/{log}/ignore', [App\Http\Controllers\AiCorrectionController::class, 'toggleIgnore'])
    ->name('corrections.ignore');

    Route::get('/ai-skill', [App\Http\Controllers\AiSkillController::class, 'index'])->name('ai-skill.index');
    Route::post('/ai-skill', [App\Http\Controllers\AiSkillController::class, 'update'])->name('ai-skill.update');

    Route::get('/customers', [App\Http\Controllers\CustomerController::class, 'index'])->name('customers.index');
    Route::patch('/customers/{customer}', [App\Http\Controllers\CustomerController::class, 'update'])->name('customers.update');
});
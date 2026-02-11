<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TicketController;

Route::get('/', function () {
    return view('welcome');
});

// Ticket routes (publiek toegankelijk)
Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
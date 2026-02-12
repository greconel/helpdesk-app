<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Toon het dashboard met alle tickets
     */
    public function index()
    {
        // Haal alle tickets op met hun relaties (customer en agent)
        $tickets = Ticket::with(['customer', 'agent', 'labels'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboard', compact('tickets'));
    }
}
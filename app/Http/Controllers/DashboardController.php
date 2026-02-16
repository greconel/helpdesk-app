<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Label;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Toon het dashboard - Status board (Kanban view op basis van workflow)
     */
    public function index(Request $request)
    {
        // Definieer alle statussen
        $statuses = [
            'new' => 'Nieuw',
            'in_progress' => 'In behandeling',
            'on_hold' => 'On hold',
            'to_close' => 'Te sluiten',
            'closed' => 'Gesloten'
        ];

        // Haal tickets op per status
        $ticketsByStatus = [];
        foreach (array_keys($statuses) as $status) {
            $ticketsByStatus[$status] = Ticket::with(['customer', 'agent', 'labels'])
                ->where('status', $status)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Return view met BEIDE variabelen
        return view('dashboard', compact('ticketsByStatus', 'statuses'));
    }

    /**
     * Toon het agents board (Kanban view op basis van agents)
     */
    public function agentsBoard()
    {
        // Haal alle agents op met hun ticket count
        $agents = User::withCount(['assignedTickets' => function($query) {
            $query->whereIn('status', ['new', 'in_progress', 'on_hold', 'to_close']);
        }])
        ->orderBy('name')
        ->get();

        // Haal niet-toegewezen tickets op (alleen open tickets)
        $unassignedTickets = Ticket::with(['customer', 'labels'])
            ->whereNull('assigned_to')
            ->whereIn('status', ['new', 'in_progress', 'on_hold', 'to_close'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('agents', compact('agents', 'unassignedTickets'));
    }
}
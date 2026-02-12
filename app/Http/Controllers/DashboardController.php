<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Label;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Toon het dashboard met alle tickets
     */
    public function index(Request $request)
    {
        // Start query
        $query = Ticket::with(['customer', 'agent', 'labels']);

        // Filter op status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter op impact
        if ($request->filled('impact')) {
            if ($request->impact === 'null') {
                // Toon tickets zonder impact
                $query->whereNull('impact');
            } else {
                $query->where('impact', $request->impact);
            }
        }

        // Filter op label
        if ($request->filled('label')) {
            $query->whereHas('labels', function($q) use ($request) {
                $q->where('labels.id', $request->label);
            });
        }

        // Filter op toegewezen aan
        if ($request->filled('assigned')) {
            if ($request->assigned === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned);
            }
        }

        // Haal tickets op
        $tickets = $query->orderBy('created_at', 'desc')->get();

        // Haal alle labels op voor de filter dropdown
        $allLabels = Label::orderBy('name')->get();

        // Haal alle users op voor de filter dropdown
        $allUsers = \App\Models\User::orderBy('name')->get();

        return view('dashboard', compact('tickets', 'allLabels', 'allUsers'));
    }
}
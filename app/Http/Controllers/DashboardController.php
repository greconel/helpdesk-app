<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\AiCorrectionLog;

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
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('agents', compact('agents', 'unassignedTickets'));
    }
    public function overview()
    {
        $totalTickets   = Ticket::count();
        $openTickets    = Ticket::whereIn('status', ['new', 'in_progress', 'on_hold', 'to_close'])->count();
        $closedTickets  = Ticket::where('status', 'closed')->count();

        $totalCorrections   = AiCorrectionLog::count();
        $unprocessed        = AiCorrectionLog::where('processed', false)->count();
        $impactOnly         = AiCorrectionLog::where('correction_type', 'impact_only')->count();
        $labelsOnly         = AiCorrectionLog::where('correction_type', 'labels_only')->count();
        $both               = AiCorrectionLog::where('correction_type', 'both')->count();

        $currentSkillVersion = 'onbekend';
        $skillPath = storage_path('ai-skill/labeling-skill.md');
        if (file_exists($skillPath)) {
            $skillContent = file_get_contents($skillPath);
            if (preg_match('/\*\*Versie:\*\*\s*(.+)/m', $skillContent, $matches)) {
                $currentSkillVersion = trim($matches[1]);
            }
        }

        return view('overview', compact(
            'totalTickets',
            'openTickets',
            'closedTickets',
            'totalCorrections',
            'unprocessed',
            'impactOnly',
            'labelsOnly',
            'both',
            'currentSkillVersion'
        ));
    }
}
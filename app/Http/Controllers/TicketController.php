<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Customer;
use App\Models\Label;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function create()
    {
        return view('tickets.create');
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'subject' => 'required|string|max:255',
        'description' => 'required|string',
    ], [
        'name.required' => 'Naam is verplicht',
        'email.required' => 'E-mailadres is verplicht',
        'email.email' => 'Voer een geldig e-mailadres in',
        'subject.required' => 'Onderwerp is verplicht',
        'description.required' => 'Beschrijving is verplicht',
    ]);

    DB::beginTransaction();
    try {
        $customer = Customer::firstOrCreate(
            ['email' => $validated['email']],
            ['name' => $validated['name']]
        );

        $ticketNumber = $this->generateTicketNumber();

        $ticket = Ticket::create([
            'ticket_number' => $ticketNumber,
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'status' => 'new',
            'impact' => null, // NIEUW: impact is null bij nieuwe tickets
            'customer_id' => $customer->id,
        ]);

        DB::commit();

        return redirect()
            ->route('tickets.create')
            ->with('success', "Uw ticket ({$ticketNumber}) is succesvol aangemaakt.");

    } catch (\Exception $e) {
        DB::rollBack();
        return back()
            ->withInput()
            ->with('error', 'Er is iets misgegaan. Probeer het opnieuw.');
    }
}

    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'impact' => 'nullable|in:low,medium,high',
            'labels' => 'array',
            'labels.*' => 'exists:labels,id',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'nullable|in:new,in_progress,on_hold,to_close,closed',
        ]);

        // Als er iemand wordt toegewezen EN de status is nog 'new', zet dan naar 'in_progress'
        if (isset($validated['assigned_to']) && $validated['assigned_to'] && $ticket->status === 'new') {
            $ticket->status = 'in_progress';
        }

        // Als de toegewezen persoon wordt verwijderd EN de status is 'in_progress', zet terug naar 'new'
        if ((!isset($validated['assigned_to']) || !$validated['assigned_to']) && $ticket->assigned_to && $ticket->status === 'in_progress') {
            $ticket->status = 'new';
        }

        // Update impact en assigned_to en status
        $ticket->update([
            'impact' => $validated['impact'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? null,
             'status' => $validated['status'] ?? $ticket->status,
               'closed_at'   => ($validated['status'] === 'closed' && $ticket->status !== 'closed')
                        ? now()
                        : ($validated['status'] !== 'closed' ? null : $ticket->closed_at),
        ]);

        // Update labels
        if ($request->has('labels')) {
            $ticket->labels()->sync($validated['labels']);
        } else {
            $ticket->labels()->sync([]);
        }

        return back()->with('success', 'Ticket succesvol bijgewerkt.');
    }
    public function show(Ticket $ticket)
    {
        $ticket->load(['customer', 'agent', 'labels']);
        
        // Haal alle beschikbare labels op
        $allLabels = Label::orderBy('name')->get();
        
        return view('tickets.show', compact('ticket', 'allLabels'));
    }

    

    private function generateTicketNumber(): string
    {
        $lastTicket = Ticket::orderBy('id', 'desc')->first();
        
        if (!$lastTicket) {
            return '#0001';
        }

        $lastNumber = (int) str_replace('#', '', $lastTicket->ticket_number);
        $newNumber = $lastNumber + 1;

        return '#' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function move(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'status'      => 'nullable|in:new,in_progress,on_hold,to_close,closed',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $newAssignedTo = array_key_exists('assigned_to', $validated) 
                            ? $validated['assigned_to'] 
                            : $ticket->assigned_to;

        // Alleen naar in_progress zetten als ticket nog NIEUW is en naar een persoon wordt gesleept
        if ($newAssignedTo && !$ticket->assigned_to && $ticket->status === 'new') {
            $newStatus = 'in_progress';
        }
        else {
            $newStatus = $validated['status'] ?? $ticket->status;
        }

        $ticket->update([
            'status'      => $newStatus,
            'assigned_to' => $newAssignedTo,
            'closed_at'   => ($newStatus === 'closed' && $ticket->status !== 'closed')
                                ? now()
                                : ($newStatus !== 'closed' ? null : $ticket->closed_at),
        ]);

        return response()->json(['success' => true]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Customer;
use App\Models\Label;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\TicketCreated;
use App\Events\TicketAssigned;

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
                'impact' => null,
                'customer_id' => $customer->id,
            ]);

            DB::commit();
            TicketCreated::dispatch($ticket);
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
            'status' => 'nullable|in:new,in_progress,on_hold,to_close,closed',
        ]);

        $ticket->update([
            'impact'  => $validated['impact'] ?? null,
            'status'  => $validated['status'] ?? $ticket->status,
            'closed_at' => ($validated['status'] === 'closed' && $ticket->status !== 'closed')
                ? now()
                : ($validated['status'] !== 'closed' ? null : $ticket->closed_at),
        ]);

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

        $previousAgent = $ticket->assigned_to;

        $newAssignedTo = array_key_exists('assigned_to', $validated)
            ? $validated['assigned_to']
            : $ticket->assigned_to;

        if ($newAssignedTo && !$ticket->assigned_to && $ticket->status === 'new') {
            $newStatus = 'in_progress';
        } else {
            $newStatus = $validated['status'] ?? $ticket->status;
        }

        $ticket->update([
            'status'      => $newStatus,
            'assigned_to' => $newAssignedTo,
            'closed_at'   => ($newStatus === 'closed' && $ticket->status !== 'closed')
                ? now()
                : ($newStatus !== 'closed' ? null : $ticket->closed_at),
        ]);

        if ($ticket->assigned_to && $ticket->assigned_to != $previousAgent) {
            TicketAssigned::dispatch($ticket, $ticket->agent);
        }

        return response()->json(['success' => true]);
    }
}
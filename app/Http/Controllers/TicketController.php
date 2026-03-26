<?php

namespace App\Http\Controllers;

use App\Events\TicketCreated;
use App\Models\Ticket;
use App\Models\Customer;
use App\Models\Label;
use App\Models\User;
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
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|max:255',
            'subject'     => 'required|string|max:255',
            'description' => 'required|string',
        ], [
            'name.required'        => 'Naam is verplicht',
            'email.required'       => 'E-mailadres is verplicht',
            'email.email'          => 'Voer een geldig e-mailadres in',
            'subject.required'     => 'Onderwerp is verplicht',
            'description.required' => 'Beschrijving is verplicht',
        ]);

        DB::beginTransaction();
        try {
            $customer = Customer::firstOrCreate(
                ['email' => $validated['email']],
                ['name'  => $validated['name']]
            );

            $ticket = Ticket::create([
                'ticket_number' => $this->generateTicketNumber(),
                'subject'       => $validated['subject'],
                'description'   => $validated['description'],
                'status'        => 'new',
                'impact'        => null,
                'customer_id'   => $customer->id,
            ]);

            event(new TicketCreated($ticket));

            DB::commit();

            return redirect()
                ->route('tickets.create')
                ->with('success', "Uw ticket ({$ticket->ticket_number}) is succesvol aangemaakt.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Er is iets misgegaan. Probeer het opnieuw.');
        }
    }

    public function agentCreate()
    {
        $labels = Label::orderBy('name')->get();
        $agents = User::orderBy('name')->get();

        return view('tickets.agent-create', compact('labels', 'agents'));
    }

    public function agentStore(Request $request)
    {
        $validated = $request->validate([
            'customer_mode'      => 'required|in:existing,new',
            'customer_id'        => 'required_if:customer_mode,existing|nullable|exists:customers,id',
            'customer_name'      => 'required_if:customer_mode,new|nullable|string|max:255',
            'customer_email'     => 'required_if:customer_mode,new|nullable|email|max:255',
            'customer_phone'     => 'nullable|string|max:50',
            'subject'            => 'required|string|max:255',
            'description'        => 'required|string',
            'impact'             => 'nullable|in:low,medium,high',
            'assigned_to'        => 'nullable|exists:users,id',
            'labels'             => 'array',
            'labels.*'           => 'exists:labels,id',
            'send_confirmation'  => 'nullable|boolean',
        ], [
            'customer_id.required_if'    => 'Selecteer een bestaande klant.',
            'customer_name.required_if'  => 'Naam is verplicht voor een nieuwe klant.',
            'customer_email.required_if' => 'E-mail is verplicht voor een nieuwe klant.',
            'subject.required'           => 'Onderwerp is verplicht.',
            'description.required'       => 'Beschrijving is verplicht.',
        ]);

        DB::beginTransaction();
        try {
            if ($validated['customer_mode'] === 'existing') {
                $customer = Customer::findOrFail($validated['customer_id']);
            } else {
                $customer = Customer::firstOrCreate(
                    ['email' => $validated['customer_email']],
                    [
                        'name'  => $validated['customer_name'],
                        'phone' => $validated['customer_phone'] ?? null,
                    ]
                );
            }

            $status = $validated['assigned_to'] ? 'in_progress' : 'new';

            $ticket = Ticket::create([
                'ticket_number' => $this->generateTicketNumber(),
                'subject'       => $validated['subject'],
                'description'   => $validated['description'],
                'status'        => $status,
                'impact'        => $validated['impact'] ?? null,
                'customer_id'   => $customer->id,
                'assigned_to'   => $validated['assigned_to'] ?? null,
            ]);

            if (!empty($validated['labels'])) {
                $ticket->labels()->sync($validated['labels']);
            }

            // Alleen event firen als bevestigingsmail gewenst is
            if ($request->boolean('send_confirmation')) {
                event(new TicketCreated($ticket));
            }

            DB::commit();

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('success', "Ticket {$ticket->ticket_number} is succesvol aangemaakt.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Er is iets misgegaan. Probeer het opnieuw.');
        }
    }

    public function searchCustomers(Request $request)
    {
        $query = $request->get('q', '');

        $customers = Customer::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json($customers);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'impact'    => 'nullable|in:low,medium,high',
            'labels'    => 'array',
            'labels.*'  => 'exists:labels,id',
            'status'    => 'nullable|in:new,in_progress,on_hold,to_close,closed',
        ]);

        $ticket->update([
            'impact'    => $validated['impact'] ?? null,
            'status'    => $validated['status'] ?? $ticket->status,
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
        $ticket->load(['customer', 'agent', 'labels', 'timeLogs.user']);
        $allLabels = Label::orderBy('name')->get();
        return view('tickets.show', compact('ticket', 'allLabels'));
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

        return response()->json(['success' => true]);
    }

    private function generateTicketNumber(): string
    {
        $lastTicket = Ticket::orderBy('id', 'desc')->first();

        if (!$lastTicket) {
            return '#0001';
        }

        $lastNumber = (int) str_replace('#', '', $lastTicket->ticket_number);
        $newNumber  = $lastNumber + 1;

        return '#' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
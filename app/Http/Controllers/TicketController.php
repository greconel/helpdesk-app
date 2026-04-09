<?php

namespace App\Http\Controllers;

use App\Events\TicketCreated;
use App\Models\Ticket;
use App\Models\Customer;
use App\Models\Label;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Ticket management controller
 * Twee flows: customer self-service (create) en agent internal (agentCreate)
 */
class TicketController extends Controller
{
    /**
     * Toon customer ticketaanmaakformulier
     */
    public function create()
    {
        return view('tickets.create');
    }

    /**
     * Sla customer ticket op (transaction: alles of niets)
     */
    public function store(Request $request)
    {
        // Validate all required fields with custom error messages
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

        // Start database transaction to ensure customer + ticket are both created or both fail
        DB::beginTransaction();
        try {
            // Retrieve or create customer record by email (prevents duplicates)
            $customer = Customer::firstOrCreate(
                ['email' => $validated['email']],
                ['name'  => $validated['name']]
            );

            // Create ticket with auto-generated number and initial status
            $ticket = Ticket::create([
                'ticket_number' => Ticket::generateTicketNumber(),
                'subject'       => $validated['subject'],
                'description'   => $validated['description'],
                'status'        => 'new',
                'impact'        => null, // Will be set by AI analysis
                'customer_id'   => $customer->id,
            ]);

            DB::commit();

            // Dispatch event: triggers async AI analysis job and email confirmation to customer
            event(new TicketCreated($ticket));

            return redirect()
                ->route('tickets.create')
                ->with('success', "Uw ticket ({$ticket->ticket_number}) is succesvol aangemaakt.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Er is iets misgegaan. Probeer het opnieuw.');
        }
    }

    /**
     * Toon agent ticketaanmaakformulier (volledige controle)
     */
    public function agentCreate()
    {
        $labels = Label::orderBy('name')->get();
        $agents = User::orderBy('name')->get();

        return view('tickets.agent-create', compact('labels', 'agents'));
    }

    /**
     * Sla agent-ticket op (status auto in_progress als assigned, anders new)
     */
    public function agentStore(Request $request)
    {
        // Complex validation rules due to conditional customer_mode logic
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
            // Route to existing or new customer based on form input
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

            // Auto-set status: if assigned to agent, mark as 'in_progress', else 'new'
            $status = $validated['assigned_to'] ? 'in_progress' : 'new';

            // Create ticket with all provided metadata
            $ticket = Ticket::create([
                'ticket_number' => Ticket::generateTicketNumber(),
                'subject'       => $validated['subject'],
                'description'   => $validated['description'],
                'status'        => $status,
                'impact'        => $validated['impact'] ?? null,
                'customer_id'   => $customer->id,
                'assigned_to'   => $validated['assigned_to'] ?? null,
            ]);

            // Attach labels via pivot table (many-to-many relationship)
            if (!empty($validated['labels'])) {
                $ticket->labels()->sync($validated['labels']);
            }

            DB::commit();

            // Dispatch event with send_confirmation flag (optional email to customer)
            event(new TicketCreated($ticket, $request->boolean('send_confirmation')));

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('success', "Ticket {$ticket->ticket_number} is succesvol aangemaakt.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Er is iets misgegaan. Probeer het opnieuw.');
        }
    }

    /**
     * AJAX: Zoek klanten op naam of email
     */
    public function searchCustomers(Request $request)
    {
        $query = $request->get('q', '');

        // Search across name and email fields, return limited columns for efficiency
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

        $impactChanged = $validated['impact'] !== $ticket->impact;

        $ticket->update([
            'impact'             => $validated['impact'] ?? null,
            'status'             => $validated['status'] ?? $ticket->status,
            'ai_labelled_impact' => $impactChanged ? false : $ticket->ai_labelled_impact,
            'closed_at'          => ($validated['status'] === 'closed' && $ticket->status !== 'closed')
                ? now()
                : ($validated['status'] !== 'closed' ? null : $ticket->closed_at),
        ]);

        if ($request->has('labels')) {
            $huidigeLabels = $ticket->labels->pluck('id')->sort()->values()->toArray();
            $nieuweLabels  = collect($validated['labels'] ?? [])->map(fn($id) => (int)$id)->sort()->values()->toArray();
            $labelsChanged = $huidigeLabels !== $nieuweLabels;

            $ticket->labels()->sync($validated['labels']);

            if ($labelsChanged) {
                $ticket->update(['ai_labelled_labels' => false]);
            }
        } else {
            $ticket->labels()->sync([]);
            $ticket->update(['ai_labelled_labels' => false]);
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
}
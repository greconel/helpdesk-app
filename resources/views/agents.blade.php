@extends('layouts.app')

@section('title', 'Agents Board - Helpdesk')
@section('page-title', 'Agents Board')

@section('content')
<div
    x-data="{
        active: ['new', 'in_progress'],
        toggle(status) {
            const idx = this.active.indexOf(status);
            if (idx !== -1) {
                if (this.active.length > 1) this.active.splice(idx, 1);
            } else {
                this.active.push(status);
            }
        },
        isActive(status) {
            return this.active.includes(status);
        },
        visibleCount(tickets) {
            return tickets.filter(t => this.active.includes(t)).length;
        }
    }"
>

    {{-- Filter toolbar --}}
    <div class="flex items-center gap-3 px-6 py-3 bg-white border-b border-gray-200 flex-wrap">
        <span class="text-xs font-medium text-gray-500">Toon statussen:</span>

        <button
            type="button"
            @click="toggle('new')"
            :class="isActive('new')
                ? 'bg-slate-100 border-slate-400 text-slate-700'
                : 'bg-white border-gray-200 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full border transition-colors">
            <span class="w-1.5 h-1.5 rounded-full bg-slate-500 flex-shrink-0"></span>
            Nieuw
        </button>

        <button
            type="button"
            @click="toggle('in_progress')"
            :class="isActive('in_progress')
                ? 'bg-blue-50 border-blue-300 text-blue-700'
                : 'bg-white border-gray-200 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full border transition-colors">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 flex-shrink-0"></span>
            In behandeling
        </button>

        <button
            type="button"
            @click="toggle('on_hold')"
            :class="isActive('on_hold')
                ? 'bg-amber-50 border-amber-300 text-amber-700'
                : 'bg-white border-gray-200 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full border transition-colors">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 flex-shrink-0"></span>
            On hold
        </button>

        <button
            type="button"
            @click="toggle('to_close')"
            :class="isActive('to_close')
                ? 'bg-violet-50 border-violet-300 text-violet-700'
                : 'bg-white border-gray-200 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full border transition-colors">
            <span class="w-1.5 h-1.5 rounded-full bg-violet-500 flex-shrink-0"></span>
            Te sluiten
        </button>
    </div>

    {{-- Board --}}
    <div class="px-6 py-6">
        <div class="flex gap-4 overflow-x-auto pb-4">

            {{-- Unassigned Column --}}
            <div class="flex-shrink-0 w-80">
                <div class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-t-lg">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-900">Niet toegewezen</h2>
                        <span
                            class="ticket-count text-xs font-semibold px-2 py-1 rounded bg-gray-100 text-gray-700 border border-gray-200"
                            x-text="{{ $unassignedTickets->count() }} - {{ $unassignedTickets->count() }} + $el.closest('.flex-shrink-0').querySelectorAll('.ticket-card:not([style*=\'display: none\'])').length"
                        >{{ $unassignedTickets->count() }}</span>
                    </div>
                </div>

                <div class="agent-column bg-white border-l border-r border-b rounded-b-lg p-3 space-y-2.5 min-h-[200px] max-h-[calc(100vh-250px)] overflow-y-auto"
                    data-agent-id="">

                    @forelse($unassignedTickets as $ticket)
                        <div
                            class="ticket-card bg-white border border-gray-200 rounded-lg p-3 hover:border-blue-400 hover:shadow-sm transition-all cursor-grab active:cursor-grabbing"
                            data-id="{{ $ticket->id }}"
                            data-status="{{ $ticket->status }}"
                            x-show="isActive('{{ $ticket->status }}')"
                        >
                            <a href="{{ route('tickets.show', ['ticket' => $ticket, 'from' => 'agents']) }}" class="block group">
                                <div class="flex items-start justify-between mb-2">
                                    <span class="text-xs font-semibold text-blue-600 group-hover:text-blue-700">
                                        {{ $ticket->ticket_number }}
                                    </span>
                                    @if($ticket->impact)
                                        @php
                                            $impactStyles = [
                                                'low' => 'bg-green-50 text-green-700 border border-green-200',
                                                'medium' => 'bg-amber-50 text-amber-700 border border-amber-200',
                                                'high' => 'bg-red-50 text-red-700 border border-red-200',
                                            ];
                                        @endphp
                                        <span class="text-xs px-2 py-0.5 rounded {{ $impactStyles[$ticket->impact] ?? '' }}">
                                            {{ ucfirst($ticket->impact) }}
                                        </span>
                                    @endif
                                </div>
                                <h3 class="text-sm font-medium text-gray-900 mb-2.5 line-clamp-2 leading-snug">
                                    {{ $ticket->subject }}
                                </h3>
                                <div class="space-y-1.5 mb-2.5">
                                    <p class="text-xs text-gray-600">
                                        <span class="font-medium">Klant:</span> {{ $ticket->customer->name }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    @php
                                        $statusStyles = [
                                            'new' => 'bg-slate-50 text-slate-700 border border-slate-200',
                                            'in_progress' => 'bg-blue-50 text-blue-700 border border-blue-200',
                                            'on_hold' => 'bg-amber-50 text-amber-700 border border-amber-200',
                                            'to_close' => 'bg-violet-50 text-violet-700 border border-violet-200',
                                        ];
                                        $statusLabels = [
                                            'new' => 'Nieuw',
                                            'in_progress' => 'In behandeling',
                                            'on_hold' => 'On hold',
                                            'to_close' => 'Te sluiten',
                                        ];
                                    @endphp
                                    <span class="text-xs px-2 py-0.5 rounded {{ $statusStyles[$ticket->status] ?? 'bg-gray-50 text-gray-700 border border-gray-200' }}">
                                        {{ $statusLabels[$ticket->status] ?? $ticket->status }}
                                    </span>
                                    @foreach($ticket->labels->take(2) as $label)
                                        <span class="text-xs px-2 py-0.5 bg-gray-50 text-gray-600 border border-gray-200 rounded">
                                            {{ $label->name }}
                                        </span>
                                    @endforeach
                                    @if($ticket->labels->count() > 2)
                                        <span class="text-xs px-2 py-0.5 text-gray-500">
                                            +{{ $ticket->labels->count() - 2 }}
                                        </span>
                                    @endif
                                </div>
                            </a>
                        </div>
                    @empty
                        <div class="empty-placeholder text-center py-12 text-gray-400 text-sm">
                            <p>Geen tickets</p>
                        </div>
                    @endforelse

                    {{-- Lege staat als alle tickets gefilterd zijn --}}
                    <div
                        class="text-center py-12 text-gray-400 text-sm"
                        x-show="{{ $unassignedTickets->count() }} > 0 && $el.closest('.agent-column').querySelectorAll('[x-show]').length > 0 && Array.from($el.closest('.agent-column').querySelectorAll('[data-status]')).every(el => el.style.display === 'none')"
                        style="display:none"
                    >
                        <p>Geen tickets voor geselecteerde statussen</p>
                    </div>
                </div>
            </div>

            {{-- Agent Columns --}}
            @foreach($agents as $agent)
                @php
                    $agentTickets = $agent->assignedTickets()
                        ->with(['customer', 'labels'])
                        ->whereIn('status', ['new', 'in_progress', 'on_hold', 'to_close'])
                        ->orderBy('updated_at', 'desc')
                        ->get();
                @endphp

                <div class="flex-shrink-0 w-80">
                    <div class="px-4 py-3 bg-blue-50 border border-blue-200 rounded-t-lg">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-gray-900">{{ $agent->name }}</h2>
                            <span
                                class="ticket-count text-xs font-semibold px-2 py-1 rounded bg-blue-100 text-blue-700 border border-blue-200"
                                x-text="'{{ $agentTickets->count() }}'"
                                id="count-agent-{{ $agent->id }}"
                            >{{ $agentTickets->count() }}</span>
                        </div>
                    </div>

                    <div class="agent-column bg-white border-l border-r border-b rounded-b-lg p-3 space-y-2.5 min-h-[200px] max-h-[calc(100vh-250px)] overflow-y-auto"
                        data-agent-id="{{ $agent->id }}">

                        @forelse($agentTickets as $ticket)
                            <div
                                class="ticket-card bg-white border border-gray-200 rounded-lg p-3 hover:border-blue-400 hover:shadow-sm transition-all cursor-grab active:cursor-grabbing"
                                data-id="{{ $ticket->id }}"
                                data-status="{{ $ticket->status }}"
                                x-show="isActive('{{ $ticket->status }}')"
                            >
                                <a href="{{ route('tickets.show', ['ticket' => $ticket, 'from' => 'agents']) }}" class="block group">
                                    <div class="flex items-start justify-between mb-2">
                                        <span class="text-xs font-semibold text-blue-600 group-hover:text-blue-700">
                                            {{ $ticket->ticket_number }}
                                        </span>
                                        @if($ticket->impact)
                                            @php
                                                $impactStyles = [
                                                    'low' => 'bg-green-50 text-green-700 border border-green-200',
                                                    'medium' => 'bg-amber-50 text-amber-700 border border-amber-200',
                                                    'high' => 'bg-red-50 text-red-700 border border-red-200',
                                                ];
                                            @endphp
                                            <span class="text-xs px-2 py-0.5 rounded {{ $impactStyles[$ticket->impact] ?? '' }}">
                                                {{ ucfirst($ticket->impact) }}
                                            </span>
                                        @endif
                                    </div>
                                    <h3 class="text-sm font-medium text-gray-900 mb-2.5 line-clamp-2 leading-snug">
                                        {{ $ticket->subject }}
                                    </h3>
                                    <div class="space-y-1.5 mb-2.5">
                                        <p class="text-xs text-gray-600">
                                            <span class="font-medium">Klant:</span> {{ $ticket->customer->name }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        @php
                                            $statusStyles = [
                                                'new' => 'bg-slate-50 text-slate-700 border border-slate-200',
                                                'in_progress' => 'bg-blue-50 text-blue-700 border border-blue-200',
                                                'on_hold' => 'bg-amber-50 text-amber-700 border border-amber-200',
                                                'to_close' => 'bg-violet-50 text-violet-700 border border-violet-200',
                                            ];
                                            $statusLabels = [
                                                'new' => 'Nieuw',
                                                'in_progress' => 'In behandeling',
                                                'on_hold' => 'On hold',
                                                'to_close' => 'Te sluiten',
                                            ];
                                        @endphp
                                        <span class="text-xs px-2 py-0.5 rounded {{ $statusStyles[$ticket->status] ?? 'bg-gray-50 text-gray-700 border border-gray-200' }}">
                                            {{ $statusLabels[$ticket->status] ?? $ticket->status }}
                                        </span>
                                        @foreach($ticket->labels->take(2) as $label)
                                            <span class="text-xs px-2 py-0.5 bg-gray-50 text-gray-600 border border-gray-200 rounded">
                                                {{ $label->name }}
                                            </span>
                                        @endforeach
                                        @if($ticket->labels->count() > 2)
                                            <span class="text-xs px-2 py-0.5 text-gray-500">
                                                +{{ $ticket->labels->count() - 2 }}
                                            </span>
                                        @endif
                                    </div>
                                </a>
                            </div>
                        @empty
                            <div class="empty-placeholder text-center py-12 text-gray-400 text-sm">
                                <p>Geen tickets</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script type="module">
    const moveUrl = (id) => `/tickets/${id}/move`;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    document.querySelectorAll('.agent-column').forEach(column => {
        Sortable.create(column, {
            group: 'agents-board',
            animation: 150,
            ghostClass: 'opacity-40',
            dragClass: 'shadow-lg',
            handle: '.ticket-card',

            onEnd(event) {
                const ticketId = event.item.dataset.id;
                const newAgentId = event.to.dataset.agentId;

                updatePlaceholders(event.from);
                updatePlaceholders(event.to);
                updateCount(event.from);
                updateCount(event.to);

                fetch(moveUrl(ticketId), {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        assigned_to: newAgentId === '' ? null : newAgentId,
                    }),
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        alert('Er ging iets mis. Probeer opnieuw.');
                        location.reload();
                    }
                })
                .catch(() => {
                    alert('Verbindingsfout. Probeer opnieuw.');
                    location.reload();
                });
            }
        });
    });

    function updatePlaceholders(column) {
        const visibleCards = Array.from(column.querySelectorAll('.ticket-card'))
            .filter(el => el.style.display !== 'none');
        let placeholder = column.querySelector('.empty-placeholder');

        if (visibleCards.length === 0) {
            if (!placeholder) {
                placeholder = document.createElement('div');
                placeholder.className = 'empty-placeholder text-center py-12 text-gray-400 text-sm';
                placeholder.innerHTML = '<p>Geen tickets</p>';
                column.appendChild(placeholder);
            }
        } else {
            if (placeholder) placeholder.remove();
        }
    }

    function updateCount(column) {
        const col = column.closest('.flex-shrink-0');
        const visibleCount = Array.from(column.querySelectorAll('.ticket-card'))
            .filter(el => el.style.display !== 'none').length;
        const badge = col.querySelector('.ticket-count');
        if (badge) badge.textContent = visibleCount;
    }

    {{-- Herbereken tellers wanneer Alpine filters wijzigen --}}
    document.addEventListener('alpine:initialized', () => {
        const observer = new MutationObserver(() => {
            document.querySelectorAll('.agent-column').forEach(column => {
                updateCount(column);
                updatePlaceholders(column);
            });
        });

        document.querySelectorAll('.agent-column').forEach(column => {
            observer.observe(column, { attributes: true, subtree: true, attributeFilter: ['style'] });
        });
    });
</script>
@endsection
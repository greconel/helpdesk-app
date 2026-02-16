@extends('layouts.app')

@section('title', 'Status Board - Helpdesk')
@section('page-title', 'Status Board')

@section('content')
<div class="px-6 py-6">
    <div class="flex gap-4 overflow-x-auto pb-4">
        @foreach($statuses as $statusKey => $statusLabel)
            @php
                $tickets = $ticketsByStatus[$statusKey];
                $columnStyles = [
                    'new' => [
                        'header' => 'bg-slate-50 border-slate-200',
                        'badge' => 'bg-slate-100 text-slate-700 border border-slate-200'
                    ],
                    'in_progress' => [
                        'header' => 'bg-blue-50 border-blue-200',
                        'badge' => 'bg-blue-100 text-blue-700 border border-blue-200'
                    ],
                    'on_hold' => [
                        'header' => 'bg-amber-50 border-amber-200',
                        'badge' => 'bg-amber-100 text-amber-700 border border-amber-200'
                    ],
                    'to_close' => [
                        'header' => 'bg-violet-50 border-violet-200',
                        'badge' => 'bg-violet-100 text-violet-700 border border-violet-200'
                    ],
                    'closed' => [
                        'header' => 'bg-emerald-50 border-emerald-200',
                        'badge' => 'bg-emerald-100 text-emerald-700 border border-emerald-200'
                    ],
                ];
                $style = $columnStyles[$statusKey] ?? ['header' => 'bg-gray-50 border-gray-200', 'badge' => 'bg-gray-100 text-gray-700'];
            @endphp
            
            <div class="flex-shrink-0 w-80">
                <!-- Column Header -->
                <div class="px-4 py-3 border rounded-t-lg {{ $style['header'] }}">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-900">{{ $statusLabel }}</h2>
                        <span class="text-xs font-semibold px-2 py-1 rounded {{ $style['badge'] }}">
                            {{ $tickets->count() }}
                        </span>
                    </div>
                </div>

                <!-- Tickets Container -->
                <div class="bg-white border-l border-r border-b rounded-b-lg p-3 space-y-2.5 min-h-[200px] max-h-[calc(100vh-200px)] overflow-y-auto">
                    @forelse($tickets as $ticket)
                        <a href="{{ route('tickets.show', ['ticket' => $ticket, 'from' => 'status']) }}" class="block bg-white border border-gray-200 rounded-lg p-3 hover:border-blue-400 hover:shadow-sm transition-all group">
                            <!-- Ticket Header -->
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

                            <!-- Subject -->
                            <h3 class="text-sm font-medium text-gray-900 mb-2.5 line-clamp-2 leading-snug">
                                {{ $ticket->subject }}
                            </h3>

                            <!-- Meta Info -->
                            <div class="space-y-1.5 mb-2.5">
                                <p class="text-xs text-gray-600">
                                    <span class="font-medium">Klant:</span> {{ $ticket->customer->name }}
                                </p>
                                @if($ticket->agent)
                                    <p class="text-xs text-gray-600">
                                        <span class="font-medium">Agent:</span> {{ $ticket->agent->name }}
                                    </p>
                                @else
                                    <p class="text-xs text-gray-400">
                                        <span class="font-medium">Agent:</span> Niet toegewezen
                                    </p>
                                @endif
                            </div>

                            <!-- Labels -->
                            @if($ticket->labels->count() > 0)
                                <div class="flex flex-wrap gap-1">
                                    @foreach($ticket->labels->take(3) as $label)
                                        <span class="text-xs px-2 py-0.5 bg-gray-50 text-gray-600 border border-gray-200 rounded">
                                            {{ $label->name }}
                                        </span>
                                    @endforeach
                                    @if($ticket->labels->count() > 3)
                                        <span class="text-xs px-2 py-0.5 text-gray-500">
                                            +{{ $ticket->labels->count() - 3 }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </a>
                    @empty
                        <div class="text-center py-12 text-gray-400 text-sm">
                            <p>Geen tickets</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
@extends('layouts.app')

@section('title', 'Ticket ' . $ticket->ticket_number . ' - Helpdesk')
@section('page-title')
    <div class="flex items-center gap-4">
        <a href="{{ request('from') === 'agents' ? route('agents.board') : route('dashboard') }}" class="text-gray-600 hover:text-gray-900">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <span>Ticket {{ $ticket->ticket_number }}</span>
    </div>
@endsection

@section('content')
    <div class="px-6 py-6">
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main ticket info -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h2 class="text-xl font-semibold text-gray-900 mb-3">{{ $ticket->subject }}</h2>
                            <div class="flex items-center gap-2 flex-wrap">
                                @php
                                    $statusColors = [
                                        'new'         => 'bg-slate-100 text-slate-700 border border-slate-200',
                                        'in_progress' => 'bg-blue-100 text-blue-700 border border-blue-200',
                                        'on_hold'     => 'bg-amber-100 text-amber-700 border border-amber-200',
                                        'to_close'    => 'bg-violet-100 text-violet-700 border border-violet-200',
                                        'closed'      => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
                                    ];
                                    $statusLabels = [
                                        'new'         => 'Nieuw',
                                        'in_progress' => 'In behandeling',
                                        'on_hold'     => 'On hold',
                                        'to_close'    => 'Te sluiten',
                                        'closed'      => 'Gesloten',
                                    ];
                                @endphp
                                <span class="px-3 py-1.5 inline-flex text-xs font-semibold rounded-lg {{ $statusColors[$ticket->status] }}">
                                    {{ $statusLabels[$ticket->status] }}
                                </span>

                                @if($ticket->impact)
                                    @php
                                        $impactStyles = [
                                            'low'    => 'bg-green-100 text-green-700 border border-green-200',
                                            'medium' => 'bg-amber-100 text-amber-700 border border-amber-200',
                                            'high'   => 'bg-red-100 text-red-700 border border-red-200',
                                        ];
                                        $impactLabels = [
                                            'low'    => 'Low impact',
                                            'medium' => 'Medium impact',
                                            'high'   => 'High impact',
                                        ];
                                    @endphp
                                    <span class="px-3 py-1.5 inline-flex text-xs font-semibold rounded-lg {{ $impactStyles[$ticket->impact] }}">
                                        {{ $impactLabels[$ticket->impact] }}
                                    </span>
                                @endif

                                @foreach($ticket->labels as $label)
                                    <span class="px-3 py-1.5 inline-flex text-xs font-semibold rounded-lg bg-gray-100 text-gray-700 border border-gray-200">
                                        {{ $label->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <div class="text-sm font-semibold text-gray-700 mb-2">Beschrijving</div>
                        <div class="text-gray-900 whitespace-pre-wrap leading-relaxed">{{ $ticket->description }}</div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Ticket eigenschappen -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Ticket eigenschappen</h3>

                    <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            @php
                                $statusLabels = [
                                    'new'         => 'Nieuw',
                                    'in_progress' => 'In behandeling',
                                    'on_hold'     => 'On hold',
                                    'to_close'    => 'Te sluiten',
                                    'closed'      => 'Gesloten',
                                ];
                            @endphp
                            <select name="status" id="status" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                                @foreach($statusLabels as $value => $label)
                                    <option value="{{ $value }}" {{ $ticket->status === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Impact -->
                        <div>
                            <label for="impact" class="block text-sm font-medium text-gray-700 mb-2">Impact</label>
                            <select name="impact" id="impact" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">Geen impact toegewezen</option>
                                <option value="low"    {{ $ticket->impact === 'low'    ? 'selected' : '' }}>Low impact</option>
                                <option value="medium" {{ $ticket->impact === 'medium' ? 'selected' : '' }}>Medium impact</option>
                                <option value="high"   {{ $ticket->impact === 'high'   ? 'selected' : '' }}>High impact</option>
                            </select>
                            @error('impact')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Labels -->
                        <div>
                            <label for="labels" class="block text-sm font-medium text-gray-700 mb-2">Labels</label>
                            <select name="labels[]" id="labels" multiple class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm" style="min-height: 120px;">
                                @foreach($allLabels as $label)
                                    <option value="{{ $label->id }}" {{ $ticket->labels->contains($label->id) ? 'selected' : '' }}>
                                        {{ $label->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1.5 text-xs text-gray-500">Houd Ctrl (of Cmd) ingedrukt om meerdere te selecteren</p>
                            @error('labels')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Toegewezen aan (readonly) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Toegewezen aan</label>
                            <div class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                                {{ $ticket->agent?->name ?? 'Niet toegewezen' }}
                            </div>
                            <p class="mt-1.5 text-xs text-gray-400">Toewijzen via het Agents Board (drag & drop)</p>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors text-sm">
                            Wijzigingen opslaan
                        </button>
                    </form>
                </div>

                <!-- Klant informatie -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Klant</h3>
                    <div class="space-y-3">
                        <div>
                            <div class="text-sm font-medium text-gray-500 mb-1">Naam</div>
                            <div class="text-sm text-gray-900">{{ $ticket->customer->name }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500 mb-1">Email</div>
                            <div class="text-sm">
                                <a href="mailto:{{ $ticket->customer->email }}" class="text-blue-600 hover:text-blue-700">
                                    {{ $ticket->customer->email }}
                                </a>
                            </div>
                        </div>
                        @if($ticket->customer->phone)
                            <div>
                                <div class="text-sm font-medium text-gray-500 mb-1">Telefoon</div>
                                <div class="text-sm text-gray-900">{{ $ticket->customer->phone }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Ticket tijdlijn -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Tijdlijn</h3>

                    <div class="space-y-3 mb-4">
                        <div>
                            <div class="text-sm font-medium text-gray-500 mb-1">Aangemaakt op</div>
                            <div class="text-sm text-gray-900">{{ $ticket->created_at->format('d-m-Y H:i') }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500 mb-1">Laatst bijgewerkt</div>
                            <div class="text-sm text-gray-900">{{ $ticket->updated_at->format('d-m-Y H:i') }}</div>
                        </div>
                        @if($ticket->closed_at)
                            <div>
                                <div class="text-sm font-medium text-gray-500 mb-1">Gesloten op</div>
                                <div class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($ticket->closed_at)->format('d-m-Y H:i') }}</div>
                            </div>
                        @endif
                    </div>

                    @php
                        $activities = $ticket->activities()->latest()->get();
                        $statusLabels = [
                            'new'         => 'Nieuw',
                            'in_progress' => 'In behandeling',
                            'on_hold'     => 'On hold',
                            'to_close'    => 'Te sluiten',
                            'closed'      => 'Gesloten',
                        ];
                    @endphp

                    @if($activities->count() > 0)
                        <div class="border-t border-gray-100 pt-4">
                            <div class="text-sm font-medium text-gray-500 mb-3">Wijzigingen</div>
                            <div class="space-y-3">
                                @foreach($activities as $activity)
                                    @php
                                        $changes = $activity->properties['attributes'] ?? [];
                                        $old     = $activity->properties['old'] ?? [];
                                    @endphp
                                    @foreach($changes as $field => $newValue)
                                        @php
                                            $oldValue = $old[$field] ?? null;
                                            $who  = $activity->causer?->name ?? 'Systeem';
                                            $when = $activity->created_at->format('d-m-Y H:i');

                                            if ($field === 'status') {
                                                $oldLabel = $statusLabels[$oldValue] ?? $oldValue;
                                                $newLabel = $statusLabels[$newValue] ?? $newValue;
                                                $text = "Status gewijzigd van <strong>{$oldLabel}</strong> naar <strong>{$newLabel}</strong>";
                                            } elseif ($field === 'assigned_to') {
                                                $oldAgent = $oldValue ? \App\Models\User::find($oldValue)?->name ?? 'Onbekend' : 'Niemand';
                                                $newAgent = $newValue ? \App\Models\User::find($newValue)?->name ?? 'Onbekend' : 'Niemand';
                                                $text = "Toegewezen van <strong>{$oldAgent}</strong> naar <strong>{$newAgent}</strong>";
                                            } elseif ($field === 'impact') {
                                                $oldLabel = $oldValue ? ucfirst($oldValue) : 'Geen';
                                                $newLabel = $newValue ? ucfirst($newValue) : 'Geen';
                                                $text = "Impact gewijzigd van <strong>{$oldLabel}</strong> naar <strong>{$newLabel}</strong>";
                                            } else {
                                                $text = "<strong>{$field}</strong> gewijzigd";
                                            }
                                        @endphp
                                        <div class="flex gap-3">
                                            <div class="w-1.5 h-1.5 rounded-full bg-blue-400 mt-1.5 flex-shrink-0"></div>
                                            <div>
                                                <div class="text-xs text-gray-900">{!! $text !!}</div>
                                                <div class="text-xs text-gray-400 mt-0.5">{{ $who }} · {{ $when }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
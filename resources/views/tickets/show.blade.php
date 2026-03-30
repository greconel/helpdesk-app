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
        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main ticket info -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Ticket beschrijving -->
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
                        <div class="text-gray-900 leading-relaxed overflow-x-auto">
                            @if($ticket->source === 'email')
                                {!! $ticket->description !!}
                            @else
                                {!! nl2br(e($ticket->description)) !!}
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ═══════════════════════════════════════════════
                     CHAT / COMMUNICATIE TIJDLIJN
                ════════════════════════════════════════════════ --}}
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-5 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        Communicatie
                    </h3>

                    {{-- Berichten tijdlijn --}}
                    <div class="space-y-4 mb-6 max-h-[600px] overflow-y-auto pr-1" id="message-timeline">
                        @forelse($ticket->messages as $msg)
                            @if($msg->direction === 'outbound')
                                {{-- Agent bericht (rechts / blauw) --}}
                                <div class="flex gap-3 justify-end">
                                    <div class="max-w-[85%]">
                                        <div class="flex items-center gap-2 justify-end mb-1">
                                            <span class="text-xs text-gray-400">
                                                {{ $msg->sent_at?->setTimezone('Europe/Brussels')->format('d-m-Y H:i') }}
                                            </span>
                                            <span class="text-xs font-semibold text-blue-700">
                                                {{ $msg->from_name ?? 'Agent' }}
                                            </span>
                                            <div class="w-6 h-6 rounded-full bg-blue-600 flex items-center justify-center flex-shrink-0">
                                                <span class="text-xs font-bold text-white">
                                                    {{ strtoupper(substr($msg->from_name ?? 'A', 0, 1)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg rounded-tr-sm px-4 py-3 text-sm text-gray-800 leading-relaxed">
                                            {!! $msg->body_html !!}
                                        </div>
                                    </div>
                                </div>
                            @else
                                {{-- Klant bericht (links / grijs) --}}
                                <div class="flex gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0 mt-1">
                                        <span class="text-xs font-bold text-gray-600">
                                            {{ strtoupper(substr($ticket->customer->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div class="max-w-[85%]">
                                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                                            <span class="text-xs font-semibold text-gray-700">
                                                {{ $ticket->customer->name }}
                                            </span>
                                            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full border border-gray-200">
                                                klant
                                            </span>
                                            <span class="text-xs text-gray-400">
                                                {{ $msg->sent_at?->setTimezone('Europe/Brussels')->format('d-m-Y H:i') }}
                                            </span>
                                        </div>
                                        <div class="bg-gray-50 border border-gray-200 rounded-lg rounded-tl-sm px-4 py-3 text-sm text-gray-800 leading-relaxed overflow-x-auto">
                                            {!! $msg->body_html !!}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <div class="text-center py-10 text-gray-400 text-sm">
                                <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <p>Nog geen berichten</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Antwoord formulier --}}
                    <form action="{{ route('tickets.reply', $ticket) }}" method="POST" class="border-t border-gray-200 pt-4">
                        @csrf
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center flex-shrink-0 mt-1">
                                <span class="text-xs font-bold text-white">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </span>
                            </div>
                            <div class="flex-1">
                                <textarea
                                    name="body"
                                    rows="3"
                                    placeholder="Schrijf een bericht aan de klant..."
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm resize-none"
                                >{{ old('body') }}</textarea>
                                @error('body')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                <div class="flex items-center justify-between mt-2">
                                    <p class="text-xs text-gray-400 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        De klant ontvangt dit als e-mail
                                    </p>
                                    <button type="submit"
                                        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                        </svg>
                                        Verstuur
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                {{-- ═══════════════════════════════════════════════
                     EINDE CHAT / COMMUNICATIE TIJDLIJN
                ════════════════════════════════════════════════ --}}

                {{-- Gelogde tijd overzicht --}}
                @if($ticket->timeLogs->count() > 0)
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Gelogde tijd</h3>

                    <div class="space-y-2 mb-4">
                        @foreach($ticket->timeLogs->sortByDesc('created_at') as $log)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">{{ $log->user->name }}</p>
                                    <p class="text-xs text-gray-400">
                                        @if($log->started_at)
                                            {{ $log->started_at->format('d-m-Y H:i') }} → {{ $log->stopped_at->format('H:i') }}
                                        @else
                                            {{ $log->created_at->format('d-m-Y H:i') }}
                                        @endif
                                    </p>
                                </div>
                                <span class="text-sm font-semibold text-gray-700">
                                    @if(floor($log->duration_minutes / 60) > 0)
                                        {{ floor($log->duration_minutes / 60) }}u
                                    @endif
                                    @if($log->duration_minutes % 60 > 0)
                                        {{ $log->duration_minutes % 60 }}m
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>

                    @php $totaal = $ticket->timeLogs->sum('duration_minutes'); @endphp
                    <div class="flex items-center justify-between pt-2 border-t border-gray-200">
                        <span class="text-sm font-semibold text-gray-700">Totaal</span>
                        <span class="text-sm font-semibold text-teal-600">
                            @if(floor($totaal / 60) > 0)
                                {{ floor($totaal / 60) }}u
                            @endif
                            {{ $totaal % 60 }}m
                        </span>
                    </div>
                </div>
                @endif

                {{-- Tijd loggen --}}
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Tijd loggen</h3>

                    {{-- Timer --}}
                    <div x-data="{
                        running: false,
                        startedAt: null,
                        elapsed: 0,
                        interval: null,
                        ticketId: '{{ $ticket->id }}',
                        init() {
                            const saved = localStorage.getItem('timer_' + this.ticketId);
                            if (saved) {
                                const data = JSON.parse(saved);
                                this.startedAt = data.startedAt;
                                this.running = true;
                                this.elapsed = Math.floor((Date.now() - new Date(data.startedAt).getTime()) / 1000);
                                this.interval = setInterval(() => {
                                    this.elapsed = Math.floor((Date.now() - new Date(this.startedAt).getTime()) / 1000);
                                }, 1000);
                            }
                        },
                        start() {
                            this.startedAt = new Date().toISOString();
                            this.running = true;
                            this.elapsed = 0;
                            localStorage.setItem('timer_' + this.ticketId, JSON.stringify({ startedAt: this.startedAt }));
                            this.interval = setInterval(() => {
                                this.elapsed = Math.floor((Date.now() - new Date(this.startedAt).getTime()) / 1000);
                            }, 1000);
                        },
                        stop() {
                            clearInterval(this.interval);
                            this.running = false;
                            localStorage.removeItem('timer_' + this.ticketId);
                            document.getElementById('timer_started_at').value = this.startedAt;
                            document.getElementById('timer_stopped_at').value = new Date().toISOString();
                            this.$nextTick(() => {
                                document.getElementById('timer-form').submit();
                            });
                        },
                        formatted() {
                            let h = Math.floor(this.elapsed / 3600);
                            let m = Math.floor((this.elapsed % 3600) / 60);
                            let s = this.elapsed % 60;
                            return [h,m,s].map(v => String(v).padStart(2,'0')).join(':');
                        }
                    }" x-init="init()" class="mb-4">
                        <div class="text-3xl font-mono text-gray-800 mb-3" x-text="formatted()">00:00:00</div>
                        <div class="flex gap-2">
                            <button type="button"
                                x-show="!running"
                                @click="start()"
                                class="flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                                Starten
                            </button>
                            <button type="button"
                                x-show="running"
                                @click="stop()"
                                class="flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M6 6h12v12H6z"/>
                                </svg>
                                Stoppen & opslaan
                            </button>
                        </div>

                        <form id="timer-form"
                            action="{{ route('timelogs.store', $ticket) }}"
                            method="POST"
                            class="hidden">
                            @csrf
                            <input type="hidden" id="timer_started_at" name="started_at">
                            <input type="hidden" id="timer_stopped_at" name="stopped_at">
                        </form>
                    </div>

                    {{-- Scheidingslijn --}}
                    <div class="flex items-center gap-3 my-4">
                        <div class="flex-1 border-t border-gray-200"></div>
                        <span class="text-xs text-gray-400">of manueel invoeren</span>
                        <div class="flex-1 border-t border-gray-200"></div>
                    </div>

                    {{-- Manuele invoer --}}
                    <form action="{{ route('timelogs.store', $ticket) }}" method="POST">
                        @csrf
                        <div class="flex gap-3 mb-3">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Uren</label>
                                <input type="number" name="hours" min="0" value="0"
                                    class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500 text-sm">
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Minuten</label>
                                <input type="number" name="duration_minutes" min="0" max="59" value="0"
                                    class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500 text-sm">
                            </div>
                        </div>
                        <button type="submit"
                            class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors text-sm">
                            Toevoegen
                        </button>
                    </form>
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
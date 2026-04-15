@extends('layouts.app')

@section('title', 'AI Skill Beheer - Helpdesk')
@section('page-title', 'AI Skill Beheer')

@section('content')
<div class="px-6 py-6 space-y-6">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Twee kolommen: editor links, correcties rechts --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        {{-- ── Skill editor ───────────────────────────────────────────── --}}
        <div class="space-y-4">
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Skill bestand</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Dit bestand bepaalt hoe de AI tickets labelt. Wijzigingen worden direct actief.
                        </p>
                    </div>
                    <span class="text-xs text-gray-400 font-mono">labeling-skill.md</span>
                </div>

                <form method="POST" action="{{ route('ai-skill.update') }}">
                    @csrf

                    <textarea
                        name="skill_content"
                        rows="32"
                        class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-xs font-mono resize-none"
                    >{{ $skillContent }}</textarea>

                    @error('skill_content')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="flex items-center justify-between mt-4">
                        <p class="text-xs text-gray-400">
                            Er wordt automatisch een backup gemaakt bij elke opslag.
                        </p>
                        <button type="submit"
                            class="flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white font-medium text-sm px-5 py-2.5 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                            </svg>
                            Opslaan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── Correcties overzicht ────────────────────────────────────── --}}
        <div class="space-y-4">
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Correcties</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Markeer correcties als uitzondering zodat de AI er niet van leert.
                        </p>
                    </div>
                    <span class="text-xs text-gray-500">
                        {{ $corrections->total() }} totaal
                    </span>
                </div>

                <div class="space-y-3">
                    @forelse($corrections as $log)
                        <div
                            x-data="{ ignore: {{ $log->ignore_in_training ? 'true' : 'false' }}, open: false }"
                            class="rounded-lg border transition-colors"
                            :class="ignore ? 'border-amber-200 bg-amber-50' : 'border-gray-200 bg-white'"
                        >
                            {{-- Header rij --}}
                            <div class="flex items-center gap-3 px-4 py-3">

                                {{-- Ticket nummer + onderwerp --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <a href="{{ route('tickets.show', $log->ticket_id) }}"
                                           class="text-xs font-semibold text-blue-600 hover:text-blue-700">
                                            {{ $log->ticket?->ticket_number ?? '#?' }}
                                        </a>
                                        <span class="text-xs text-gray-600 truncate">
                                            {{ Str::limit($log->ticket_subject, 50) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                                        {{-- Type badge --}}
                                        @php
                                            $typeBadge = [
                                                'impact_only'  => ['bg-blue-50 text-blue-700 border-blue-200',   'Alleen impact'],
                                                'labels_only'  => ['bg-violet-50 text-violet-700 border-violet-200', 'Alleen labels'],
                                                'both'         => ['bg-red-50 text-red-700 border-red-200',      'Impact + labels'],
                                            ][$log->correction_type] ?? ['bg-gray-50 text-gray-600 border-gray-200', $log->correction_type];
                                        @endphp
                                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded border {{ $typeBadge[0] }}">
                                            {{ $typeBadge[1] }}
                                        </span>

                                        {{-- Verwerkt badge --}}
                                        @if($log->processed)
                                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded border bg-emerald-50 text-emerald-700 border-emerald-200">
                                                Verwerkt
                                            </span>
                                        @else
                                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded border bg-gray-50 text-gray-500 border-gray-200">
                                                Onverwerkt
                                            </span>
                                        @endif

                                        <span class="text-[10px] text-gray-400">
                                            {{ $log->created_at->format('d-m-Y H:i') }}
                                            @if($log->agent) · {{ $log->agent->name }} @endif
                                        </span>
                                    </div>
                                </div>

                                {{-- Toggle uitzondering --}}
                                <form method="POST"
                                      action="{{ route('corrections.ignore', $log) }}"
                                      x-ref="form{{ $log->id }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="ignore_in_training" :value="ignore ? '1' : '0'">
                                    <input type="hidden" name="ignore_reason" value="{{ $log->ignore_reason }}">
                                </form>

                                <button type="button"
                                    @click="ignore = !ignore; $nextTick(() => $refs.form{{ $log->id }}.submit())"
                                    :class="ignore ? 'bg-amber-500' : 'bg-gray-200'"
                                    class="relative flex-shrink-0 w-10 h-5 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-1"
                                    :title="ignore ? 'Klik om uitzondering op te heffen' : 'Klik om als uitzondering te markeren'">
                                    <span
                                        :class="ignore ? 'translate-x-5' : 'translate-x-1'"
                                        class="block w-3.5 h-3.5 bg-white rounded-full shadow transition-transform">
                                    </span>
                                </button>

                                {{-- Details toggle --}}
                                <button type="button"
                                    @click="open = !open"
                                    class="text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0">
                                    <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                            </div>

                            {{-- Uitklapbare details --}}
                            <div x-show="open" x-cloak class="border-t border-gray-100 px-4 py-3 space-y-3">
                                <div class="grid grid-cols-2 gap-3 text-xs">
                                    <div class="bg-purple-50 border border-purple-100 rounded-lg p-3">
                                        <p class="font-semibold text-purple-700 mb-1">AI voorstel</p>
                                        <p class="text-gray-700">Impact: <strong>{{ $log->ai_impact ?? '—' }}</strong></p>
                                        <p class="text-gray-700">Labels: <strong>{{ implode(', ', $log->ai_labels ?? []) ?: '—' }}</strong></p>
                                        <p class="text-gray-500 mt-1">Skill: {{ $log->ai_skill_version ?? '—' }}</p>
                                    </div>
                                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-3">
                                        <p class="font-semibold text-blue-700 mb-1">Agent correctie</p>
                                        <p class="text-gray-700">Impact: <strong>{{ $log->agent_impact ?? '—' }}</strong></p>
                                        <p class="text-gray-700">Labels: <strong>{{ implode(', ', $log->agent_labels ?? []) ?: '—' }}</strong></p>
                                        <p class="text-gray-500 mt-1">Door: {{ $log->agent?->name ?? '—' }}</p>
                                    </div>
                                </div>

                                {{-- Reden veld (alleen als uitzondering aan) --}}
                                <div x-show="ignore">
                                    <form method="POST" action="{{ route('corrections.ignore', $log) }}" class="flex gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="ignore_in_training" value="1">
                                        <input
                                            type="text"
                                            name="ignore_reason"
                                            value="{{ $log->ignore_reason }}"
                                            placeholder="Reden voor uitzondering (optioneel)…"
                                            class="flex-1 rounded-lg border-gray-300 focus:border-amber-400 focus:ring-amber-400 text-xs"
                                        >
                                        <button type="submit"
                                            class="px-3 py-1.5 bg-amber-100 hover:bg-amber-200 text-amber-800 text-xs font-medium rounded-lg transition-colors border border-amber-200">
                                            Opslaan
                                        </button>
                                    </form>
                                </div>

                                @if($log->ignore_in_training && $log->ignore_reason)
                                    <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                                        <strong>Reden:</strong> {{ $log->ignore_reason }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 text-gray-400 text-sm">
                            Nog geen correcties gevonden.
                        </div>
                    @endforelse
                </div>

                {{-- Paginering --}}
                @if($corrections->hasPages())
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        {{ $corrections->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
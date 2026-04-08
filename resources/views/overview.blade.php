@extends('layouts.app')

@section('title', 'Overview - Helpdesk')
@section('page-title', 'Overview')

@section('content')
<div class="px-6 py-6">

    {{-- Ticket statistieken --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Total tickets</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ \App\Models\Ticket::count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Open tickets</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ \App\Models\Ticket::whereIn('status', ['new', 'in_progress', 'on_hold', 'to_close'])->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Closed tickets</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ \App\Models\Ticket::where('status', 'closed')->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- AI Correcties sectie --}}
    @php
        $totalCorrections   = \App\Models\AiCorrectionLog::count();
        $unprocessed        = \App\Models\AiCorrectionLog::where('processed', false)->count();
        $impactOnly         = \App\Models\AiCorrectionLog::where('correction_type', 'impact_only')->count();
        $labelsOnly         = \App\Models\AiCorrectionLog::where('correction_type', 'labels_only')->count();
        $both               = \App\Models\AiCorrectionLog::where('correction_type', 'both')->count();
        $currentSkillVersion = 'onbekend';
        $skillPath = storage_path('ai-skill/labeling-skill.md');
        if (file_exists($skillPath)) {
            $skillContent = file_get_contents($skillPath);
            if (preg_match('/\*\*Versie:\*\*\s*(.+)/m', $skillContent, $matches)) {
                $currentSkillVersion = trim($matches[1]);
            }
        }
    @endphp

    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-base font-semibold text-gray-900">AI Correcties</h2>
        <div class="flex items-center gap-3">
            <span class="text-xs text-gray-500">Skill versie: <span class="font-semibold text-purple-700">{{ $currentSkillVersion }}</span></span>
            <a href="{{ route('corrections.export') }}"
               class="flex items-center gap-2 bg-white border border-gray-200 hover:border-blue-400 hover:bg-blue-50 text-gray-700 hover:text-blue-700 text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                AI-Correcties Export
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Totaal correcties</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $totalCorrections }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Nog te verwerken</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $unprocessed }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Impact gecorrigeerd</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $impactOnly + $both }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 10V5a2 2 0 012-2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Labels gecorrigeerd</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $labelsOnly + $both }}</p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
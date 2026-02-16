@extends('layouts.app')

@section('title', 'Overview - Helpdesk')
@section('page-title', 'Overview')

@section('content')
<div class="px-6 py-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Total Tickets Card -->
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

        <!-- Open Tickets Card -->
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

        <!-- Closed Tickets Card -->
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

    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Toekomstige statistieken</h3>
        <p class="text-gray-600">Hier komen binnenkort grafieken en statistieken...</p>
    </div>
</div>
@endsection
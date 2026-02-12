<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Helpdesk</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Helpdesk Dashboard</h1>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded text-sm">
                            Uitloggen
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Stats -->
        <div class="mb-8 grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500">Nieuw</div>
                <div class="mt-1 text-3xl font-semibold text-blue-600">
                    {{ $tickets->where('status', 'new')->count() }}
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500">In behandeling</div>
                <div class="mt-1 text-3xl font-semibold text-yellow-600">
                    {{ $tickets->where('status', 'in_progress')->count() }}
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500">On hold</div>
                <div class="mt-1 text-3xl font-semibold text-orange-600">
                    {{ $tickets->where('status', 'on_hold')->count() }}
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500">Te sluiten</div>
                <div class="mt-1 text-3xl font-semibold text-purple-600">
                    {{ $tickets->where('status', 'to_close')->count() }}
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500">Gesloten</div>
                <div class="mt-1 text-3xl font-semibold text-green-600">
                    {{ $tickets->where('status', 'closed')->count() }}
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow mb-6 p-4">
            <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">Alle statussen</option>
                        <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>Nieuw</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In behandeling</option>
                        <option value="on_hold" {{ request('status') === 'on_hold' ? 'selected' : '' }}>On hold</option>
                        <option value="to_close" {{ request('status') === 'to_close' ? 'selected' : '' }}>Te sluiten</option>
                        <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Gesloten</option>
                    </select>
                </div>

                <!-- Impact Filter -->
                <div>
                    <label for="impact" class="block text-sm font-medium text-gray-700 mb-1">Impact</label>
                    <select name="impact" id="impact" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">Alle impacts</option>
                        <option value="null" {{ request('impact') === 'null' ? 'selected' : '' }}>Geen impact</option>
                        <option value="low" {{ request('impact') === 'low' ? 'selected' : '' }}>Low impact</option>
                        <option value="medium" {{ request('impact') === 'medium' ? 'selected' : '' }}>Medium impact</option>
                        <option value="high" {{ request('impact') === 'high' ? 'selected' : '' }}>High impact</option>
                    </select>
                </div>

                <!-- Label Filter -->
                <div>
                    <label for="label" class="block text-sm font-medium text-gray-700 mb-1">Label</label>
                    <select name="label" id="label" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">Alle labels</option>
                        @foreach($allLabels as $label)
                            <option value="{{ $label->id }}" {{ request('label') == $label->id ? 'selected' : '' }}>
                                {{ $label->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Toegewezen aan Filter -->
                <div>
                    <label for="assigned" class="block text-sm font-medium text-gray-700 mb-1">Toegewezen aan</label>
                    <select name="assigned" id="assigned" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">Iedereen</option>
                        <option value="unassigned" {{ request('assigned') === 'unassigned' ? 'selected' : '' }}>Niet toegewezen</option>
                        @foreach($allUsers as $user)
                            <option value="{{ $user->id }}" {{ request('assigned') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Buttons -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                        Filteren
                    </button>
                    <a href="{{ route('dashboard') }}" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded text-sm font-medium text-center">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Active Filters Display -->
        @if(request()->hasAny(['status', 'impact', 'label', 'assigned']))
            <div class="mb-4 flex items-center gap-2 text-sm">
                <span class="text-gray-600 font-medium">Actieve filters:</span>
                @if(request('status'))
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full">
                        Status: {{ ucfirst(str_replace('_', ' ', request('status'))) }}
                    </span>
                @endif
                @if(request('impact'))
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full">
                        Impact: {{ request('impact') === 'null' ? 'Geen' : ucfirst(request('impact')) }}
                    </span>
                @endif
                @if(request('label'))
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full">
                        Label: {{ $allLabels->firstWhere('id', request('label'))->name ?? 'Onbekend' }}
                    </span>
                @endif
                @if(request('assigned'))
                    <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full">
                        Toegewezen: {{ request('assigned') === 'unassigned' ? 'Niet toegewezen' : ($allUsers->firstWhere('id', request('assigned'))->name ?? 'Onbekend') }}
                    </span>
                @endif
                <span class="text-gray-500">({{ $tickets->count() }} {{ $tickets->count() === 1 ? 'ticket' : 'tickets' }})</span>
            </div>
        @endif

        <!-- Tickets Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    Alle Tickets 
                    <span class="text-gray-500 font-normal">({{ $tickets->count() }})</span>
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ticket
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Onderwerp
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Klant
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Impact
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Toegewezen aan
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Labels
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aangemaakt
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($tickets as $ticket)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="font-medium text-blue-600 hover:text-blue-800">
                                        {{ $ticket->ticket_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $ticket->subject }}</div>
                                    <div class="text-sm text-gray-500">{{ Str::limit($ticket->description, 50) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $ticket->customer->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $ticket->customer->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'new' => 'bg-blue-100 text-blue-800',
                                            'in_progress' => 'bg-yellow-100 text-yellow-800',
                                            'on_hold' => 'bg-orange-100 text-orange-800',
                                            'to_close' => 'bg-purple-100 text-purple-800',
                                            'closed' => 'bg-green-100 text-green-800',
                                        ];
                                        $statusLabels = [
                                            'new' => 'Nieuw',
                                            'in_progress' => 'In behandeling',
                                            'on_hold' => 'On hold',
                                            'to_close' => 'Te sluiten',
                                            'closed' => 'Gesloten',
                                        ];
                                    @endphp
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$ticket->status] }}">
                                        {{ $statusLabels[$ticket->status] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($ticket->impact)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $ticket->impact_color }}">
                                            {{ $ticket->impact_label }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $ticket->agent ? $ticket->agent->name : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($ticket->labels as $label)
                                            <span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">
                                                {{ $label->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $ticket->created_at->format('d-m-Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                    @if(request()->hasAny(['status', 'impact', 'label', 'assigned']))
                                        Geen tickets gevonden met de huidige filters
                                    @else
                                        Geen tickets gevonden
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
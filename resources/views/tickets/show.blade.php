<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $ticket->ticket_number }} - Helpdesk</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-gray-900">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">Ticket {{ $ticket->ticket_number }}</h1>
                </div>
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
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main ticket info -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ $ticket->subject }}</h2>
                            <div class="flex items-center gap-2 flex-wrap">
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
                                <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full {{ $statusColors[$ticket->status] }}">
                                    {{ $statusLabels[$ticket->status] }}
                                </span>
                                
                                <!-- Impact badge - alleen tonen als er een impact is -->
                                @if($ticket->impact)
                                    <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full {{ $ticket->impact_color }}">
                                        {{ $ticket->impact_label }}
                                    </span>
                                @endif

                                <!-- Labels -->
                                @foreach($ticket->labels as $label)
                                    <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full bg-gray-100 text-gray-700">
                                        {{ $label->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="border-t pt-4 mt-4">
                        <div class="text-sm font-medium text-gray-500 mb-2">Beschrijving:</div>
                        <div class="text-gray-900 whitespace-pre-wrap">{{ $ticket->description }}</div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Ticket eigenschappen (bewerkbaar) -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Ticket eigenschappen</h3>
                    
                    <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <!-- Impact (single select dropdown) -->
                        <div>
                            <label for="impact" class="block text-sm font-medium text-gray-700 mb-1">
                                Impact
                            </label>
                            <select 
                                name="impact" 
                                id="impact" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Geen impact toegewezen</option>
                                <option value="low" {{ $ticket->impact === 'low' ? 'selected' : '' }}>Low impact</option>
                                <option value="medium" {{ $ticket->impact === 'medium' ? 'selected' : '' }}>Medium impact</option>
                                <option value="high" {{ $ticket->impact === 'high' ? 'selected' : '' }}>High impact</option>
                            </select>
                            @error('impact')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Labels (multi select dropdown) -->
                        <div>
                            <label for="labels" class="block text-sm font-medium text-gray-700 mb-1">
                                Labels
                            </label>
                            <select 
                                name="labels[]" 
                                id="labels" 
                                multiple
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                style="min-height: 120px;"
                            >
                                @foreach($allLabels as $label)
                                    <option 
                                        value="{{ $label->id }}" 
                                        {{ $ticket->labels->contains($label->id) ? 'selected' : '' }}
                                    >
                                        {{ $label->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Houd Ctrl (of Cmd) ingedrukt om meerdere te selecteren</p>
                            @error('labels')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Toegewezen aan -->
                        <div>
                            <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-1">
                                Toegewezen aan
                            </label>
                            <select 
                                name="assigned_to" 
                                id="assigned_to" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Niet toegewezen</option>
                                @foreach(\App\Models\User::all() as $user)
                                    <option value="{{ $user->id }}" {{ $ticket->assigned_to === $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('assigned_to')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit button -->
                        <button 
                            type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md transition duration-150"
                        >
                            Wijzigingen opslaan
                        </button>
                    </form>
                </div>

                <!-- Klant informatie -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Klant</h3>
                    <div class="space-y-3">
                        <div>
                            <div class="text-sm font-medium text-gray-500">Naam</div>
                            <div class="text-gray-900">{{ $ticket->customer->name }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500">Email</div>
                            <div class="text-gray-900">
                                <a href="mailto:{{ $ticket->customer->email }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $ticket->customer->email }}
                                </a>
                            </div>
                        </div>
                        @if($ticket->customer->phone)
                            <div>
                                <div class="text-sm font-medium text-gray-500">Telefoon</div>
                                <div class="text-gray-900">{{ $ticket->customer->phone }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Ticket details -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Tijdlijn</h3>
                    <div class="space-y-3">
                        <div>
                            <div class="text-sm font-medium text-gray-500">Aangemaakt op</div>
                            <div class="text-gray-900">{{ $ticket->created_at->format('d-m-Y H:i') }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500">Laatst bijgewerkt</div>
                            <div class="text-gray-900">{{ $ticket->updated_at->format('d-m-Y H:i') }}</div>
                        </div>
                        @if($ticket->closed_at)
                            <div>
                                <div class="text-sm font-medium text-gray-500">Gesloten op</div>
                                <div class="text-gray-900">{{ \Carbon\Carbon::parse($ticket->closed_at)->format('d-m-Y H:i') }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
@extends('layouts.app')

@section('title', 'Ticket aanmaken - Helpdesk')
@section('page-title', 'Ticket aanmaken')

@section('content')
<div class="px-6 py-6 max-w-4xl">

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('tickets.agent.store') }}" x-data="ticketForm()">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ── Linker kolom: hoofdvelden ──────────────────────────────── --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Klant selecteren / aanmaken --}}
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Klant</h3>

                    {{-- Toggle bestaand / nieuw --}}
                    <div class="flex rounded-lg border border-gray-200 overflow-hidden mb-5 w-fit">
                        <button type="button"
                            @click="customerMode = 'existing'"
                            :class="customerMode === 'existing'
                                ? 'bg-blue-600 text-white'
                                : 'bg-white text-gray-600 hover:bg-gray-50'"
                            class="px-4 py-2 text-sm font-medium transition-colors">
                            Bestaande klant
                        </button>
                        <button type="button"
                            @click="customerMode = 'new'"
                            :class="customerMode === 'new'
                                ? 'bg-blue-600 text-white'
                                : 'bg-white text-gray-600 hover:bg-gray-50'"
                            class="px-4 py-2 text-sm font-medium border-l border-gray-200 transition-colors">
                            Nieuwe klant
                        </button>
                    </div>

                    <input type="hidden" name="customer_mode" :value="customerMode">

                    {{-- Bestaande klant zoeken --}}
                    <div x-show="customerMode === 'existing'" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Zoek klant <span class="text-red-500">*</span>
                        </label>

                        <div class="relative">
                            <input
                                type="text"
                                x-model="customerSearch"
                                @input.debounce.300ms="searchCustomers()"
                                @click.outside="dropdownOpen = false"
                                placeholder="Zoek op naam of e-mail…"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm pr-10"
                                autocomplete="off"
                            >

                            {{-- Spinner --}}
                            <div x-show="searching" class="absolute right-3 top-1/2 -translate-y-1/2">
                                <svg class="animate-spin w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                </svg>
                            </div>

                            {{-- Dropdown resultaten --}}
                            <div x-show="dropdownOpen && results.length > 0"
                                 x-transition
                                 class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                                <template x-for="c in results" :key="c.id">
                                    <button type="button"
                                        @click="selectCustomer(c)"
                                        class="w-full flex items-start gap-3 px-4 py-3 hover:bg-blue-50 text-left border-b border-gray-100 last:border-0 transition-colors">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 flex-shrink-0 flex items-center justify-center mt-0.5">
                                            <span class="text-xs font-semibold text-blue-700" x-text="c.name.charAt(0).toUpperCase()"></span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900" x-text="c.name"></p>
                                            <p class="text-xs text-gray-500" x-text="c.email"></p>
                                        </div>
                                    </button>
                                </template>
                            </div>

                            {{-- Geen resultaten --}}
                            <div x-show="dropdownOpen && results.length === 0 && customerSearch.length > 1 && !searching"
                                 class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg px-4 py-3 text-sm text-gray-500">
                                Geen klanten gevonden.
                                <button type="button"
                                    @click="customerMode = 'new'; dropdownOpen = false"
                                    class="text-blue-600 hover:underline ml-1">
                                    Nieuwe klant aanmaken?
                                </button>
                            </div>
                        </div>

                        {{-- Geselecteerde klant tonen --}}
                        <div x-show="selectedCustomer" class="mt-3">
                            <input type="hidden" name="customer_id" :value="selectedCustomer?.id">
                            <div class="flex items-center justify-between bg-blue-50 border border-blue-200 rounded-lg px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-blue-200 flex items-center justify-center flex-shrink-0">
                                        <span class="text-sm font-bold text-blue-800"
                                              x-text="selectedCustomer?.name?.charAt(0)?.toUpperCase()"></span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900" x-text="selectedCustomer?.name"></p>
                                        <p class="text-xs text-gray-500" x-text="selectedCustomer?.email"></p>
                                    </div>
                                </div>
                                <button type="button" @click="clearCustomer()"
                                    class="text-gray-400 hover:text-red-500 transition-colors p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        @error('customer_id')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nieuwe klant formulier --}}
                    <div x-show="customerMode === 'new'" x-cloak class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Naam <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="customer_name"
                                    value="{{ old('customer_name') }}"
                                    placeholder="Jan Janssen"
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm @error('customer_name') border-red-500 @enderror">
                                @error('customer_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    E-mail <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="customer_email"
                                    value="{{ old('customer_email') }}"
                                    placeholder="jan@voorbeeld.be"
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm @error('customer_email') border-red-500 @enderror">
                                @error('customer_email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Telefoon <span class="text-xs text-gray-400">(optioneel)</span>
                            </label>
                            <input type="text" name="customer_phone"
                                value="{{ old('customer_phone') }}"
                                placeholder="+32 498 00 00 00"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                    </div>
                </div>

                {{-- Ticket details --}}
                <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-5">
                    <h3 class="text-base font-semibold text-gray-900">Ticket details</h3>

                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Onderwerp <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="subject" name="subject"
                            value="{{ old('subject') }}"
                            placeholder="Korte omschrijving van het probleem"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm @error('subject') border-red-500 @enderror">
                        @error('subject')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Beschrijving <span class="text-red-500">*</span>
                        </label>
                        <textarea id="description" name="description" rows="6"
                            placeholder="Geef een gedetailleerde beschrijving van het probleem of de vraag…"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

            </div>

            {{-- ── Rechter kolom: eigenschappen + acties ───────────────────── --}}
            <div class="space-y-6">

                <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-5">
                    <h3 class="text-base font-semibold text-gray-900">Eigenschappen</h3>

                    <div>
                        <label for="impact" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Impact
                        </label>
                        <select name="impact" id="impact"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Geen impact</option>
                            <option value="low"    {{ old('impact') === 'low'    ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ old('impact') === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high"   {{ old('impact') === 'high'   ? 'selected' : '' }}>High</option>
                        </select>
                    </div>

                    <div>
                        <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Toewijzen aan
                        </label>
                        <select name="assigned_to" id="assigned_to"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Niet toegewezen</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}"
                                    {{ old('assigned_to') == $agent->id ? 'selected' : '' }}>
                                    {{ $agent->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-gray-400">
                            Bij toewijzing wordt de status automatisch "In behandeling"
                        </p>
                    </div>

                    <div>
                        <label for="labels" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Labels
                        </label>
                        <select name="labels[]" id="labels" multiple
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                            style="min-height: 110px;">
                            @foreach($labels as $label)
                                <option value="{{ $label->id }}"
                                    {{ in_array($label->id, old('labels', [])) ? 'selected' : '' }}>
                                    {{ $label->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-gray-400">
                            Houd Ctrl (of Cmd) ingedrukt voor meerdere labels
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-3">
                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-lg transition-colors text-sm">
                        Ticket aanmaken
                    </button>
                    <a href="{{ route('dashboard') }}"
                       class="block text-center w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2.5 px-4 rounded-lg transition-colors text-sm">
                        Annuleren
                    </a>
                </div>

                {{-- Bevestigingsmail toggle --}}
                <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                    <div>
                        <label for="send_confirmation" class="text-sm font-medium text-gray-700">
                            Bevestigingsmail versturen
                        </label>
                        <p class="text-xs text-gray-400 mt-0.5">Stuur een bevestiging naar de klant</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" 
                            id="send_confirmation" 
                            name="send_confirmation" 
                            value="1" 
                            checked
                            class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

            </div>
        </div>
    </form>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>

<script>
function ticketForm() {
    return {
        customerMode:     '{{ old('customer_mode', 'existing') }}',
        customerSearch:   '',
        selectedCustomer: null,
        results:          [],
        searching:        false,
        dropdownOpen:     false,

        async searchCustomers() {
            const q = this.customerSearch.trim();
            if (q.length < 2) {
                this.results     = [];
                this.dropdownOpen = false;
                return;
            }
            this.searching = true;
            try {
                const res    = await fetch(
                    `/api/customers/search?q=${encodeURIComponent(q)}`,
                    { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
                );
                this.results      = await res.json();
                this.dropdownOpen = true;
            } catch (e) {
                this.results = [];
            } finally {
                this.searching = false;
            }
        },

        selectCustomer(customer) {
            this.selectedCustomer = customer;
            this.customerSearch   = '';
            this.results          = [];
            this.dropdownOpen     = false;
        },

        clearCustomer() {
            this.selectedCustomer = null;
            this.customerSearch   = '';
        },
    };
}
</script>
@endsection
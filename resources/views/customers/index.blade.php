@extends('layouts.app')

@section('title', 'Klanten - Helpdesk')
@section('page-title', 'Klanten')

@section('content')
<div class="px-6 py-6">

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50">
                    <th class="text-left px-4 py-3 font-semibold text-gray-700">Naam</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-700">E-mail</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-700">Tickets</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-700">Motion Project ID</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $customer)
                    <tr class="border-b border-gray-100 hover:bg-gray-50" x-data="{ editing: false }">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $customer->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $customer->email }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $customer->tickets_count }}</td>
                        <td class="px-4 py-3">
                            <span x-show="!editing" class="font-mono text-xs text-gray-500">
                                {{ $customer->motion_project_id ?? '—' }}
                            </span>
                            <form x-show="editing" x-cloak
                                method="POST"
                                action="{{ route('customers.update', $customer) }}">
                                @csrf
                                @method('PATCH')
                                <input
                                    type="text"
                                    name="motion_project_id"
                                    value="{{ $customer->motion_project_id }}"
                                    placeholder="Motion project ID..."
                                    class="rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-xs font-mono w-64"
                                >
                                <button type="submit"
                                    class="ml-2 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors">
                                    Opslaan
                                </button>
                                <button type="button" @click="editing = false"
                                    class="ml-1 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-medium rounded-lg transition-colors">
                                    Annuleren
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            <button type="button" x-show="!editing" @click="editing = true"
                                class="text-xs text-blue-600 hover:text-blue-700 font-medium">
                                Bewerken
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<style>[x-cloak] { display: none !important; }</style>
@endsection
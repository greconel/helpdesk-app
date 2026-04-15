<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Helpdesk')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar - Icon only, fixed, 56px wide -->
        <aside class="w-14 bg-white border-r border-gray-200 flex-shrink-0 flex flex-col">

            <!-- Nav icons -->
            <nav class="flex-1 flex flex-col items-center py-3 gap-1">

                <!-- Overview -->
                <a href="{{ route('overview') }}"
                   title="Overview"
                   class="group relative flex items-center justify-center w-10 h-10 rounded-lg transition-colors
                          {{ request()->routeIs('overview') ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="pointer-events-none absolute left-full ml-2 px-2 py-1 text-xs font-medium text-white bg-gray-900 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-50">
                        Overview
                    </span>
                </a>

                <!-- Status Board -->
                <a href="{{ route('dashboard') }}"
                   title="Status Board"
                   class="group relative flex items-center justify-center w-10 h-10 rounded-lg transition-colors
                          {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span class="pointer-events-none absolute left-full ml-2 px-2 py-1 text-xs font-medium text-white bg-gray-900 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-50">
                        Status Board
                    </span>
                </a>

                <!-- Agents Board -->
                <a href="{{ route('agents.board') }}"
                   title="Agents Board"
                   class="group relative flex items-center justify-center w-10 h-10 rounded-lg transition-colors
                          {{ request()->routeIs('agents.board') ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="pointer-events-none absolute left-full ml-2 px-2 py-1 text-xs font-medium text-white bg-gray-900 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-50">
                        Agents Board
                    </span>
                </a>

                <!-- Ticket aanmaken -->
                <a href="{{ route('tickets.agent.create') }}"
                   title="Ticket aanmaken"
                   class="group relative flex items-center justify-center w-10 h-10 rounded-lg transition-colors
                          {{ request()->routeIs('tickets.agent.create') ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4v16m8-8H4"/>
                    </svg>
                    <span class="pointer-events-none absolute left-full ml-2 px-2 py-1 text-xs font-medium text-white bg-gray-900 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-50">
                        Ticket aanmaken
                    </span>
                </a>
                <!-- Klanten -->
                <a href="{{ route('customers.index') }}"
                title="Klanten"
                class="group relative flex items-center justify-center w-10 h-10 rounded-lg transition-colors
                        {{ request()->routeIs('customers.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="pointer-events-none absolute left-full ml-2 px-2 py-1 text-xs font-medium text-white bg-gray-900 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-50">
                        Klanten
                    </span>
                </a>

                <!-- AI Skill -->
                <a href="{{ route('ai-skill.index') }}"
                title="AI Skill Beheer"
                class="group relative flex items-center justify-center w-10 h-10 rounded-lg transition-colors
                        {{ request()->routeIs('ai-skill.*') ? 'bg-purple-50 text-purple-700' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.347.347a5 5 0 01-1.651.928l-1.39.39A2 2 0 019.56 18h-.12a2 2 0 01-1.907-1.383l-.39-1.39a5 5 0 01.928-1.651l.347-.347z"/>
                    </svg>
                    <span class="pointer-events-none absolute left-full ml-2 px-2 py-1 text-xs font-medium text-white bg-gray-900 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-50">
                        AI Skill Beheer
                    </span>
                </a>

            </nav>

            <!-- User avatar + logout onderaan -->
            <div class="flex flex-col items-center gap-2 pb-3 border-t border-gray-100 pt-3">

                <div class="group relative">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center cursor-default">
                        <span class="text-xs font-semibold text-blue-700">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </span>
                    </div>
                    <span class="pointer-events-none absolute left-full ml-2 bottom-0 px-2 py-1 text-xs font-medium text-white bg-gray-900 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-50">
                        {{ auth()->user()->name }}
                    </span>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            title="Uitloggen"
                            class="group relative flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span class="pointer-events-none absolute left-full ml-2 px-2 py-1 text-xs font-medium text-white bg-gray-900 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-50">
                            Uitloggen
                        </span>
                    </button>
                </form>

            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Header - sticky -->
            <header class="bg-white border-b border-gray-200 flex-shrink-0 sticky top-0 z-10">
                <div class="px-6 py-4">
                    <h2 class="text-xl font-semibold text-gray-900">@yield('page-title')</h2>
                </div>
            </header>

            <!-- Page Content - scrollbaar -->
            <main class="flex-1 overflow-auto">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
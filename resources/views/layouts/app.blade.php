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
    <div class="flex min-h-screen">
        <!-- Sidebar - Fixed -->
        <aside class="w-64 bg-white border-r border-gray-200 flex-shrink-0 fixed left-0 top-0 bottom-0 flex flex-col">
            <div class="p-6">
                <h1 class="text-xl font-semibold text-gray-900">Helpdesk</h1>
            </div>
            
            <nav class="px-4 space-y-1 flex-1 overflow-y-auto">
                <!-- Overview -->
                <a href="{{ route('overview') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('overview') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Overview</span>
                </a>

                <!-- Status Board -->
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span>Status Board</span>
                </a>

                <!-- Agents Board -->
                <a href="{{ route('agents.board') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('agents.board') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span>Agents Board</span>
                </a>
            </nav>

            <!-- User section onderaan - blijft vast -->
            <div class="border-t border-gray-200 bg-white p-4 mt-auto">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-sm font-semibold text-blue-700">{{ substr(auth()->user()->name, 0, 1) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-gray-600 transition-colors" title="Uitloggen">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content - Met margin-left om ruimte te maken voor fixed sidebar -->
        <div class="flex-1 flex flex-col ml-64">
            <!-- Top Header -->
            <header class="bg-white border-b border-gray-200">
                <div class="px-6 py-4">
                    <h2 class="text-xl font-semibold text-gray-900">@yield('page-title')</h2>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-auto">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
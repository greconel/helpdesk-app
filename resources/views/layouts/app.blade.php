<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($title) ? $title : trim($__env->yieldContent('title', 'Helpdesk')) }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">

        @include('layouts.navigation')

        <div class="flex-1 flex flex-col min-w-0">
            <header class="bg-white border-b border-gray-200 flex-shrink-0 sticky top-0 z-10">
                <div class="px-6 py-4">
                    @isset($header)
                        <div class="text-xl font-semibold text-gray-900">
                            {{ $header }}
                        </div>
                    @else
                        <h2 class="text-xl font-semibold text-gray-900">@yield('page-title')</h2>
                    @endisset
                </div>
            </header>

            <main class="flex-1 overflow-auto">
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </main>
        </div>

    </div>
</body>
</html>
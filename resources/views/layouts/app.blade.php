<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- This allows child views to inject custom CSS here -->
        @stack('styles')
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <!-- Page Heading -->
            @include('partials.header')

            <!-- Page Content -->
            <main>
                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot ?? '' }}
                @endif
            </main>
        </div>

        <!-- Floating Documentation Button -->
        <a href="{{ route('docs.index') }}"
           style="position: fixed; bottom: 24px; right: 24px; width: 56px; height: 56px; z-index: 9999;"
           class="bg-[#f09520] hover:bg-[#d68420] text-white rounded-full shadow-lg flex items-center justify-center transition-all duration-300 hover:scale-110 group"
           title="Documentation">
            <i class="fa-solid fa-book text-xl"></i>
            <span style="position: absolute; right: 100%; margin-right: 12px; white-space: nowrap; pointer-events: none;"
                  class="bg-gray-800 text-white text-sm px-3 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                Documentation
            </span>
        </a>

        <!-- This allows child views to inject custom JS here -->
        @stack('scripts')
    </body>
</html>

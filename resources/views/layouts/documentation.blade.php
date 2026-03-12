<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Documentation' }} - {{ config('app.name', 'BTSPlay') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Styles personnalisés pour la documentation */
        .docs-container {
            display: block;
            min-height: calc(100vh - 80px);
            background: #fafafa;
            padding-left: 300px;
        }

        .docs-sidebar {
            width: 300px;
            background: linear-gradient(to bottom, #ffffff, #fefefe);
            border-right: 1px solid #e0e0e0;
            padding: 1.5rem 0;
            position: fixed;
            top: 60px;
            left: 0;
            height: calc(100vh - 60px);
            overflow-y: auto;
            box-shadow: 2px 0 8px rgba(0,0,0,0.03);
            z-index: 10;
        }

        /* Scrollbar personnalisée pour la sidebar */
        .docs-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .docs-sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .docs-sidebar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 3px;
        }

        .docs-sidebar::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        .docs-content {
            padding: 3rem 4rem;
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            min-height: calc(100vh - 80px);
        }

        .docs-nav-section {
            margin-bottom: 2rem;
            padding: 0 0.5rem;
        }

        .docs-nav-title {
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #9ca3af;
            padding: 0.75rem 1.25rem;
            letter-spacing: 0.08em;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .docs-nav-title i {
            color: #f59e0b;
        }

        .docs-nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.25rem;
            color: #4b5563;
            text-decoration: none;
            transition: all 0.25s ease;
            font-size: 0.9rem;
            border-radius: 0.5rem;
            margin: 0.25rem 0;
            position: relative;
        }

        .docs-nav-link i {
            opacity: 0.6;
            transition: opacity 0.25s ease;
        }

        .docs-nav-link:hover {
            background-color: #fff8e6;
            color: #f59e0b;
            transform: translateX(4px);
        }

        .docs-nav-link:hover i {
            opacity: 1;
        }

        .docs-nav-link.active {
            background-color: #fef3c7;
            color: #f59e0b;
            font-weight: 600;
            border-left: 4px solid #f59e0b;
            padding-left: calc(1.25rem - 4px);
        }

        .docs-nav-link.active i {
            opacity: 1;
        }

        .docs-breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2.5rem;
            margin-top: 1rem;
            font-size: 0.875rem;
            color: #6b7280;
            padding-top: 1rem;
            border-top: 1px solid #f3f4f6;
        }

        .docs-breadcrumb a {
            color: #f59e0b;
            text-decoration: none;
        }

        .docs-breadcrumb a:hover {
            text-decoration: underline;
        }

        .docs-page-title {
            font-size: 2.75rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 3px solid #f59e0b;
            line-height: 1.2;
        }

        .docs-section {
            margin-bottom: 3rem;
        }

        .docs-section h2 {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1.25rem;
            margin-top: 3rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f3f4f6;
        }

        .docs-section h3 {
            font-size: 1.375rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1rem;
            margin-top: 2rem;
        }

        .docs-section p {
            line-height: 1.8;
            color: #4b5563;
            margin-bottom: 1.25rem;
            font-size: 1.025rem;
        }

        .docs-section ul {
            list-style: none;
            padding-left: 0;
            margin-bottom: 1.5rem;
        }

        .docs-section ul li {
            padding: 0.625rem 0;
            padding-left: 1.75rem;
            position: relative;
            color: #4b5563;
            line-height: 1.7;
            font-size: 1rem;
        }

        .docs-section ul li:before {
            content: "▸";
            position: absolute;
            left: 0.5rem;
            color: #f59e0b;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .docs-section ul li strong {
            color: #1f2937;
            font-weight: 600;
        }

        .docs-navigation-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }

        .docs-nav-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            text-decoration: none;
            color: #374151;
            transition: all 0.2s;
        }

        .docs-nav-button:hover {
            border-color: #f59e0b;
            color: #f59e0b;
            background-color: #fffbeb;
        }

        .docs-note {
            background-color: #eff6ff;
            border-left: 5px solid #3b82f6;
            padding: 1.25rem 1.5rem;
            margin: 2rem 0;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .docs-note p {
            margin-bottom: 0.5rem;
        }

        .docs-warning {
            background-color: #fef3c7;
            border-left: 5px solid #f59e0b;
            padding: 1.25rem 1.5rem;
            margin: 2rem 0;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .docs-warning p {
            margin-bottom: 0.5rem;
        }

        .docs-screenshot {
            margin: 1.5rem 0;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .docs-screenshot img {
            width: 100%;
            display: block;
        }
    </style>

    @stack('styles')

    <style>
        /* Masquer le bouton flottant de documentation quand on est déjà dans la doc */
        body .fixed.bottom-6.right-6 {
            display: none !important;
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        @include('partials.header')

        <div class="docs-container">
            <!-- Sidebar Navigation -->
            <aside class="docs-sidebar">
                <div class="docs-nav-section">
                    <div class="docs-nav-title">
                        <i class="fa-solid fa-book mr-1"></i>
                        Documentation
                    </div>
                    <a href="{{ route('docs.index') }}" class="docs-nav-link {{ request()->routeIs('docs.index') ? 'active' : '' }}">
                        <i class="fa-solid fa-house mr-2 text-xs"></i>
                        Accueil
                    </a>
                    <a href="{{ route('docs.getting-started') }}" class="docs-nav-link {{ request()->routeIs('docs.getting-started') ? 'active' : '' }}">
                        <i class="fa-solid fa-rocket mr-2 text-xs"></i>
                        Guide de démarrage
                    </a>
                </div>

                <div class="docs-nav-section">
                    <div class="docs-nav-title">
                        <i class="fa-solid fa-desktop mr-1"></i>
                        Interface utilisateur
                    </div>
                    <a href="{{ route('docs.interface.home') }}" class="docs-nav-link {{ request()->routeIs('docs.interface.home') ? 'active' : '' }}">
                        <i class="fa-solid fa-table-cells mr-2 text-xs"></i>
                        Page d'accueil
                    </a>
                    <a href="{{ route('docs.interface.navbar') }}" class="docs-nav-link {{ request()->routeIs('docs.interface.navbar') ? 'active' : '' }}">
                        <i class="fa-solid fa-bars mr-2 text-xs"></i>
                        Barre de navigation
                    </a>
                    <a href="{{ route('docs.interface.video-player') }}" class="docs-nav-link {{ request()->routeIs('docs.interface.video-player') ? 'active' : '' }}">
                        <i class="fa-solid fa-play-circle mr-2 text-xs"></i>
                        Lecteur vidéo
                    </a>
                    <a href="{{ route('docs.interface.search') }}" class="docs-nav-link {{ request()->routeIs('docs.interface.search') ? 'active' : '' }}">
                        <i class="fa-solid fa-magnifying-glass mr-2 text-xs"></i>
                        Recherche
                    </a>
                </div>

                @auth
                    @if(Auth::user()->hasRole('professeur') || Auth::user()->hasRole('admin'))
                        <div class="docs-nav-section">
                            <div class="docs-nav-title">
                                <i class="fa-solid fa-shield-halved mr-1"></i>
                                Administration
                            </div>
                            <a href="{{ route('docs.admin.overview') }}" class="docs-nav-link {{ request()->routeIs('docs.admin.overview') ? 'active' : '' }}">
                                <i class="fa-solid fa-chart-line mr-2 text-xs"></i>
                                Vue d'ensemble
                            </a>
                            <a href="{{ route('docs.admin.database') }}" class="docs-nav-link {{ request()->routeIs('docs.admin.database') ? 'active' : '' }}">
                                <i class="fa-solid fa-database mr-2 text-xs"></i>
                                Base de données
                            </a>
                            <a href="{{ route('docs.admin.transfers') }}" class="docs-nav-link {{ request()->routeIs('docs.admin.transfers') ? 'active' : '' }}">
                                <i class="fa-solid fa-arrows-rotate mr-2 text-xs"></i>
                                Transferts
                            </a>
                            <a href="{{ route('docs.admin.reconciliation') }}" class="docs-nav-link {{ request()->routeIs('docs.admin.reconciliation') ? 'active' : '' }}">
                                <i class="fa-solid fa-code-compare mr-2 text-xs"></i>
                                Réconciliation
                            </a>
                            <a href="{{ route('docs.admin.settings') }}" class="docs-nav-link {{ request()->routeIs('docs.admin.settings') ? 'active' : '' }}">
                                <i class="fa-solid fa-cog mr-2 text-xs"></i>
                                Paramètres
                            </a>
                            <a href="{{ route('docs.admin.users') }}" class="docs-nav-link {{ request()->routeIs('docs.admin.users') ? 'active' : '' }}">
                                <i class="fa-solid fa-users mr-2 text-xs"></i>
                                Utilisateurs
                            </a>
                        </div>
                    @endif
                @endauth
            </aside>

            <!-- Main Content -->
            <main class="docs-content">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>

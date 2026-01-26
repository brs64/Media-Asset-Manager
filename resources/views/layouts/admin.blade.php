@extends('layouts.app')

@section('content')
<div class="pt-32 pb-12 bg-gray-50 min-h-screen">
    <div class="max-w-[95%] mx-auto px-4 sm:px-6 lg:px-8">
        
        <h1 class="text-3xl font-bold text-gray-800 text-center mb-10">
            Administration de BTSPlay
        </h1>

        <div class="flex flex-wrap justify-center gap-3 mb-8">
            
            {{-- Helper function to determine active class --}}
            @php
                function getTabClass($routeName) {
                    $isActive = request()->routeIs($routeName);
                    return $isActive 
                        ? 'bg-[#b91c1c] ring-2 ring-offset-2 ring-red-700 scale-105 text-white font-bold py-2 px-4 rounded shadow transition-all capitalize duration-200' 
                        : 'bg-[#E6A23C] hover:bg-[#d49230] text-white font-bold py-2 px-4 rounded shadow transition-all capitalize duration-200';
                }
            @endphp
            <a href="{{ route('admin.database') }}" class="{{ getTabClass('admin.database') }}">
                Base de données
            </a>

            <a href="{{ route('admin.reconciliation') }}" class="{{ getTabClass('admin.reconciliation') }}">
                Réconciliation
            </a>

            <a href="{{ route('admin.transferts') }}" class="{{ getTabClass('admin.transferts') }}">
                Fonction de transfert
            </a>

            <a href="{{ route('admin.settings') }}" class="{{ getTabClass('admin.settings') }}">
                Paramétrage du site
            </a>

            <a href="{{ route('admin.logs') }}" class="{{ getTabClass('admin.logs') }}">
                Consulter les logs
            </a>

            <a href="{{ route('admin.users') }}" class="{{ getTabClass('admin.users') }}">
                Gérer les utilisateurs
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 min-h-[600px]">
            @yield('tab_content')
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @stack('admin_scripts')
@endpush
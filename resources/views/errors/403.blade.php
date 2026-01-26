@extends('layouts.app')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center">
    <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8 text-center">
        <div class="mb-4">
            <i class="fa-solid fa-lock text-6xl text-red-500"></i>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 mb-4">Accès refusé</h1>

        <p class="text-gray-600 mb-6">
            @if(isset($exception) && $exception->getMessage())
                {{ $exception->getMessage() }}
            @else
                Vous n'avez pas les permissions nécessaires pour accéder à cette page.
            @endif
        </p>

        @if(Auth::check() && Auth::user()->isProfesseur())
            <p class="text-sm text-gray-500 mb-6">
                Si vous pensez que c'est une erreur, contactez un administrateur.
            </p>
        @endif

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg transition-colors">
                <i class="fa-solid fa-home mr-2"></i>
                Retour à l'accueil
            </a>

            @if(url()->previous() != url()->current())
            <button onclick="history.back()" class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                Retour
            </button>
            @endif
        </div>
    </div>
</div>
@endsection
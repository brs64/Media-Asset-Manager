@extends('layouts.app')

{{-- Suppression des balises HTML structurelles (html, head, body) car elles sont gérées par layouts/app.blade.php --}}

{{-- On ajoute les styles et scripts spécifiques à cette page --}}
@push('styles')
    @vite(['resources/css/menuArbo.css', 'resources/css/home.css'])
@endpush

@section('content')

    @include('menuArbo')

    <div class="container mx-auto px-4">
        <div class="sliderVideo my-8">
            <h2 class="text-2xl font-bold mb-6">Dernières vidéos</h2>
            
            {{-- Remplacement du Slider par une Grille Tailwind --}}
            {{-- grid-cols-1 (mobile) -> grid-cols-4 (desktop) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach ($medias as $media)
                    <div class="video-card group">
                        <a href="{{ route('medias.show', $media->id) }}" class="block">
                            {{-- Ajout de w-full pour que l'image prenne toute la largeur de la colonne --}}
                            <div class='miniature relative overflow-hidden rounded-lg shadow-md transition-transform transform group-hover:scale-105 bg-gray-700'>
                                <img src="{{ route('thumbnails.show', $media->id) }}"
                                     alt='Miniature de la vidéo'
                                     class='imageMiniature w-full h-auto object-cover aspect-video'
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />
                                {{-- Icône de clap de cinéma en fallback --}}
                                <div class="hidden flex w-full aspect-video items-center justify-center bg-gray-700">
                                    <svg class="w-12 h-12 text-gray-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m12.296 3.464 3.02 3.956"/>
                                        <path d="M20.2 6 3 11l-.9-2.4c-.3-1.1.3-2.2 1.3-2.5l13.5-4c1.1-.3 2.2.3 2.5 1.3z"/>
                                        <path d="M3 11h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                        <path d="m6.18 5.276 3.1 3.899"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="mt-2 text-lg font-semibold text-gray-800 group-hover:text-blue-600">{{ $media->mtd_tech_titre }}</h3>
                        </a>
                    </div>
                @endforeach
            </div>
            
            {{-- Pagination --}}
            <div class="mt-6">
                {{ $medias->links() }}
            </div>
        </div>

    </div>

@endsection

{{-- Scripts Swiper supprimés car plus nécessaires --}}
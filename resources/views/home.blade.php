@extends('layouts.app')

{{-- Suppression des balises HTML structurelles (html, head, body) car elles sont gérées par layouts/app.blade.php --}}

{{-- On ajoute les styles et scripts spécifiques à cette page --}}
@push('styles')
    <link href="{{ asset('resources/css/menuArbo.css') }}" rel="stylesheet">
    <link href="{{ asset('resources/css/home.css') }}" rel="stylesheet">
@endpush

@section('content')

    @include('menuArbo')

    <div class="container mx-auto px-4">
        <div class="sliderVideo my-8">
            <h2 class="text-2xl font-bold mb-6">Vos vidéos</h2>
            
            {{-- Remplacement du Slider par une Grille Tailwind --}}
            {{-- grid-cols-1 (mobile) -> grid-cols-4 (desktop) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach ($medias as $media)
                    <div class="video-card group">
                        <a href="{{ route('medias.show', $media->id) }}" class="block">
                            {{-- Ajout de w-full pour que l'image prenne toute la largeur de la colonne --}}
                            <div class='miniature relative overflow-hidden rounded-lg shadow-md transition-transform transform group-hover:scale-105'>
                                <img src="{{ asset($media->cheminMiniatureComplet) }}" alt='Miniature de la vidéo' class='imageMiniature w-full h-auto object-cover aspect-video'/>
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

        {{-- Section Dernier Projet --}}
        @if (!empty($tabDernierProjet))
            <div class="sliderVideoProjet my-8">
                <h2 class="text-2xl font-bold mb-6">{{ $tabDernierProjet[0]["projet"] }}</h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach ($tabDernierProjet as $video)
                        <div class="video-card group">
                            <a href="{{ route('medias.show', ['v' => $video['id']]) }}" class="block">
                                <div class='miniature relative overflow-hidden rounded-lg shadow-md transition-transform transform group-hover:scale-105'>
                                    <img src="{{ route('thumbnails.show', $video['id']) }}" alt='Miniature de la vidéo' class='imageMiniature w-full h-auto object-cover aspect-video' loading='lazy'/>
                                </div>
                                <h3 class="mt-2 text-lg font-semibold">{{ $video['titre'] }}</h3>
                                <h4 class="text-sm text-gray-600">{{ $video['titreVideo'] }}</h4>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        
    </div>

@endsection

{{-- Scripts Swiper supprimés car plus nécessaires --}}
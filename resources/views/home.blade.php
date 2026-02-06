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
                            <div class='miniature relative overflow-hidden rounded-lg shadow-md transition-transform transform group-hover:scale-105'>
                                <img loading="lazy" src="{{ route('thumbnails.show', $media->id) }}" alt='Miniature de la vidéo' class='imageMiniature w-full h-auto object-cover aspect-video'/>
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
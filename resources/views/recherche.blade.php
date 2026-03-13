@extends('layouts.app')

@push('styles')
    @vite(['resources/css/recherche.css', 'resources/css/home.css'])
    <link rel="stylesheet" href="{{ asset('ressources/lib/Tagify/tagify.css') }}">
@endpush

@section('content')

    <div class="container mx-auto px-4 my-8">
        
        {{-- --- SECTION FILTRES --- --}}
        <div class="filtrage bg-white p-4 rounded-lg shadow mb-6">
            <form action="{{ route('search') }}" method="get">
                <input placeholder="Rechercher dans la description" type="text" name="description" class="description w-full p-2 border border-gray-300 rounded mb-4" 
                       value="{{ $description ?? '' }}">
                
                       <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                            @foreach($medias as $media)
                                <div class="video-card group">
                                    <a href="{{ route('medias.show', $media->id) }}" class="block">
                                        
                                        {{-- Miniature --}}
                                        <div class='miniature relative overflow-hidden rounded-lg shadow-md transition-transform transform group-hover:scale-105 bg-gray-700'>
                                            <img src="{{ route('thumbnails.show', $media->id) }}"
                                                alt="{{ $media->mtd_tech_titre }}"
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

                                        <div class="mt-2">
                                            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-blue-600">
                                                {{ $media->mtd_tech_titre }}
                                            </h3>
                                            
                                            @if($media->description)
                                                <p class="text-sm text-gray-600 mt-1 line-clamp-2">
                                                    {{ $media->description }}
                                                </p>
                                            @endif
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                
                <div class="flex gap-4">
                    <button type="button" id="add-role" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                        Ajouter un rôle
                    </button>
                    <input type="submit" value="Rechercher" id="Valider" class="bg-orange-500 text-white px-4 py-2 rounded cursor-pointer hover:bg-red-600 transition">
                </div>
            </form>
        </div>
        
        {{-- Bouton Toggle Filtres --}}
        <a href="#" class="btn-afficher-filtres block mx-auto my-4 w-8 h-8 text-gray-600 hover:text-black transition-colors">
            <svg fill="currentColor" viewBox="0 0 16 16" class="w-full h-full transform transition-transform duration-300">
                <path fill-rule="evenodd" d="m12.14 8.753-5.482 4.796c-.646.566-1.658.106-1.658-.753V3.204a1 1 0 0 1 1.659-.753l5.48 4.796a1 1 0 0 1 0 1.506z"></path>
            </svg>
        </a>


        {{-- --- SECTION RÉSULTATS (GRID) --- --}}
        <div class="mt-8">
            <h2 class="text-xl font-bold mb-4">Résultats de la recherche</h2>

            @if($medias->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach($medias as $media)
                        <div class="video-card group">
                            {{-- Corrected: $media is an Object, so we use ->id --}}
                            <a href="{{ route('medias.show', $media->id) }}" class="block h-full">
                                
                                {{-- Miniature --}}
                                <div class='miniature relative overflow-hidden rounded-lg shadow-md transition-transform transform group-hover:scale-105 bg-gray-700'>
                                    <img src="{{ route('thumbnails.show', $media->id) }}"
                                         alt="{{ $media->mtd_tech_titre }}"
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

                                    {{-- Overlay --}}
                                    <div class="absolute inset-0 bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-300 flex items-center justify-center">
                                    </div>
                                </div>

                                {{-- Infos --}}
                                <div class="mt-3">
                                    <h3 class="text-lg font-semibold text-gray-800 group-hover:text-blue-600 leading-tight">
                                        {{ $media->mtd_tech_titre }}
                                    </h3>
                                    @if($media->description)
                                        <p class="text-sm text-gray-600 mt-1 line-clamp-2">
                                            {{ $media->description }}
                                        </p>
                                    @endif
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-8">
                    {{ $medias->links() }}
                </div>
            @else
                <div class="text-center py-10 text-gray-500">
                    <p class="text-xl">Aucun résultat trouvé.</p>
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('ressources/lib/Tagify/tagify.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Vérification que la fonction existe avant de l'appeler pour éviter les erreurs
            if (typeof gererFiltres === 'function') {
                gererFiltres();
            }
        });
    </script>
@endpush
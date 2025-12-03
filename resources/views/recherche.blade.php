@extends('layouts.app')

@push('styles')
    {{-- On garde le CSS pour le formulaire de filtrage (haut de page) --}}
    @vite(['resources/css/recherche.css'])
    <link rel="stylesheet" href="{{ asset('ressources/lib/Tagify/tagify.css') }}">
@endpush

@section('content')

    <div class="container mx-auto px-4 my-8">
        
        {{-- --- SECTION FILTRES --- --}}
        {{-- J'ai gardé votre structure de formulaire existante pour ne pas casser le JS --}}
        <div class="filtrage bg-white p-4 rounded-lg shadow mb-6">
            <form action="{{ route('recherche') }}" method="get">
                <input placeholder="Rechercher dans la description" type="text" name="description" class="description w-full p-2 border border-gray-300 rounded mb-4" 
                       value="{{ $description ?? '' }}">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 selects">
                    <select name="prof" class="w-full p-2 border border-gray-300 rounded">
                        <option value="" disabled selected>Professeur référent</option>
                        @foreach ($listeProf as $profItem)
                            <option value="{{ $profItem["professeurReferent"] }}" 
                                    {{ ($prof ?? '') == $profItem["professeurReferent"] ? 'selected' : '' }}>
                                {{ $profItem["nom"] }} {{ $profItem["prenom"] }}
                            </option>
                        @endforeach
                    </select>
                    
                    <select name="projet" class="w-full p-2 border border-gray-300 rounded">
                        <option value="" disabled selected>Projet</option>
                        @foreach ($listeProjet as $projetItem)
                            <option value="{{ $projetItem["intitule"] }}"
                                    {{ ($projet ?? '') == $projetItem["intitule"] ? 'selected' : '' }}>
                                {{ $projetItem["intitule"] }}
                            </option>
                        @endforeach
                    </select>
                    
                    <input type="text" placeholder="Promotion" name="promotion" value="{{ $promotion ?? '' }}" class="w-full p-2 border border-gray-300 rounded">
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
                            {{-- Note: Assurez-vous que $media est bien un objet. Si c'est un tableau, utilisez $media['id'] --}}
                            <a href="{{ route('medias.show', $media->id ?? $media['id']) }}" class="block h-full">
                                
                                {{-- Miniature --}}
                                <div class='miniature relative overflow-hidden rounded-lg shadow-md transition-transform transform group-hover:scale-105'>
                                    <img src="{{ route('thumbnails.show', $media->id ?? $media['id']) }}"
                                         alt="{{ $media->mtd_tech_titre ?? $media['mtd_tech_titre'] }}"
                                         class='imageMiniature w-full h-auto object-cover aspect-video'/>
                                    
                                    {{-- Overlay Play Icon (Optionnel pour le style) --}}
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-300 flex items-center justify-center">
                                        {{-- Vous pouvez ajouter une icône play ici si vous voulez --}}
                                    </div>
                                </div>

                                {{-- Infos --}}
                                <div class="mt-3">
                                    <h3 class="text-lg font-semibold text-gray-800 group-hover:text-blue-600 leading-tight">
                                        {{ $media->mtd_tech_titre ?? $media['mtd_tech_titre'] }}
                                    </h3>
                                    @if(isset($media['description']) || isset($media->description))
                                        <p class="text-sm text-gray-600 mt-1 line-clamp-2">
                                            {{ $media->description ?? $media['description'] }}
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
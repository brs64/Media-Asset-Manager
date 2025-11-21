@extends('layouts.app')

@push('styles')
    <link href="{{ asset('ressources/Style/recherche.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('ressources/lib/Tagify/tagify.css') }}">
@endpush

@section('content')

    {{-- ATTENTION : Le bloc PHP initial (logique de recherche et appel des contrôleurs) a été retiré. 
        Toutes les variables ($medias, $listeProf, $listeProjet, $prof, $description, etc.) 
        DOIVENT être passées à la vue par ton Contrôleur. --}}

    <div class="filtrage">
        {{-- L'action pointe vers la route de recherche --}}
        <form action="{{ route('recherche') }}" method="get">
            <input placeholder="Rechercher dans la description" type="text" name="description" class="description" 
                   value="{{ $description ?? '' }}"> {{-- Ajout de la valeur actuelle --}}
            <div>
                <div class="selects">
                    <select name="prof" id="">
                        <option value="" disabled selected>Professeur référent</option>
                        @foreach ($listeProf as $profItem) {{-- Renommé $profItem pour éviter un conflit avec la variable $prof passée au contrôleur --}}
                            <option value="{{ $profItem["professeurReferent"] }}" 
                                    {{ ($prof ?? '') == $profItem["professeurReferent"] ? 'selected' : '' }}>
                                {{ $profItem["nom"] }} {{ $profItem["prenom"] }}
                            </option>
                        @endforeach
                    </select>
                    
                    <select placeholder="Projet" name="projet" id="">
                        <option value="" disabled selected>Projet</option>
                        @foreach ($listeProjet as $projetItem)
                            <option value="{{ $projetItem["intitule"] }}"
                                    {{ ($projet ?? '') == $projetItem["intitule"] ? 'selected' : '' }}>
                                {{ $projetItem["intitule"] }}
                            </option>
                        @endforeach
                    </select>
                    
                    <input type="text" placeholder="promotion" name="promotion" value="{{ $promotion ?? '' }}">
                </div>    
            </div>
            <button type="button" id="add-role" class="form-button">Ajouter un rôle</button>
            <input type="submit" value="Rechercher" id="Valider">
        </form>
    </div>
    
    <a href="#" class="btn-afficher-filtres">
        <svg fill="#000" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="m12.14 8.753-5.482 4.796c-.646.566-1.658.106-1.658-.753V3.204a1 1 0 0 1 1.659-.753l5.48 4.796a1 1 0 0 1 0 1.506z"></path>
        </svg>
    </a>


    <div class="resultsContainer">
        @foreach($medias as $media)
            <div class="result">
                {{-- Lien vers la page video --}}
                <a href="{{ route('video.show', ['v' => $media['id']]) }}">
                    <div class="miniature">
                        {{-- J'utilise asset() et je suppose que la fonction trouverNomMiniature est disponible ou le chemin est complet --}}
                        <img src="{{ asset('/stockage/' . $media['URI_STOCKAGE_LOCAL'] . trouverNomMiniature($media['mtd_tech_titre'])) }}" alt="">
                    </div>
                    <div class="info-video">
                        <p class="titre-video">
                            {{ $media["mtd_tech_titre"] }}
                        </p>
                        <p class="description">
                            {{ $media["description"] }}
                        </p>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('ressources/lib/Tagify/tagify.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            gererFiltres();
        });
    </script>
@endpush
@extends('layouts.app')

@push('styles')
    @vite(['resources/css/formulaire.css'])
    <link rel="stylesheet" href="{{ asset('ressources/lib/Tagify/tagify.css') }}">
@endpush

@section('content')

{{-- ATTENTION : Le bloc PHP initial a été retiré. Toutes les variables (idVideo, nomFichier, listeProfesseurs, etc.) 
    DOIVENT être passées à la vue par ton Contrôleur. --}}

<div class="form-container">
    {{-- Le action doit pointer vers ta route de mise à jour --}}
    <form method="post" action="{{ isset($media) ? route('video.update', ['v' => $media->id]) : route('media.store') }}" class="metadata-form" id="metadataForm">
        @csrf
        @if(isset($media))
            @method('PUT') {{-- Utilisation de la méthode HTTP PUT/PATCH pour la mise à jour --}}
        @endif

        <div class="form-columns">
            <div class="form-column-left">
                @isset($media)
                    <div class="thumbnail-container">
                        <img src="{{ route('thumbnails.show', $media->id) }}" alt="Miniature de la vidéo" class="thumbnail-image">
                    </div>
                    <h2 class="video-filename">{{ $nomFichier ?? $media->mtd_tech_titre }}</h2>
                    <h2 class="video-title">{{ $titreVideo ?? $media->mtd_tech_titre }}</h2>
                @else
                    <h2 class="video-filename">Nouveau média</h2>
                @endisset

                <div class="low-column-left">
                    <table class="video-info-table">
                        <tr>
                            <th>Durée</th>
                            <td>{{ $mtdTech['mtd_tech_duree'] }}</td>
                        </tr>
                        <tr>
                            <th>Images par seconde</th>
                            <td>{{ $mtdTech['mtd_tech_fps'] }}</td>
                        </tr>
                        <tr>
                            <th>Résolution</th>
                            <td>{{ $mtdTech['mtd_tech_resolution'] }}</td>
                        </tr>
                        <tr>
                            <th>Format</th>
                            <td>{{ $mtdTech['mtd_tech_format'] }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="form-column-right">
                <h2 class="team-title">Formulaire métadonnées</h2>
                <input type="hidden" name="action" value="ModifierMetadonnees">
                @isset($media)
                    <input type="hidden" name="idVideo" value="{{ $media->id }}">
                @endisset

                <div class="form-field">
                    <label for="profReferent" class="form-label">Professeur référent</label>
                    <select id="profReferent" name="profReferent" class="form-select">
                        {{-- Option sélectionnée par défaut (valeur actuelle) --}}
                        <option value="{{ $mtdEdito['professeur'] }}">
                            {{ $mtdEdito['professeur'] }}
                        </option>
                        
                        @php
                        // Tri alphabétique de la liste des professeurs
                        sort($listeProfesseurs, SORT_LOCALE_STRING);
                        @endphp

                        @foreach ($listeProfesseurs as $prof)
                            <option value="{{ $prof }}">
                                {{ $prof }}
                            </option>
                        @endforeach
                    </select>
                </div>


                <div class="form-field">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" maxlength="800" pattern="^(?! ).*(?<! )$" title="Ne commencez ni ne terminez par un espace"
                      class="form-input">{{ $description }}</textarea>
                </div>

                <div class="form-field">
                    <label for="promotion" class="form-label">Promotion</label>
                    <input type="text" id="promotion" maxlength="50" name="promotion" pattern="^(?! ).*(?<! )$" title="Ne commencez ni ne terminez par un espace"
                  value="{{ $promotion }}" class="form-input">
                </div>

                <div class="form-field">
                    <label for="projet" class="form-label">Projet</label>
                    <input type="text" id="projet" maxlength="50" name="projet" pattern="^(?! ).*(?<! )$" title="Ne commencez ni ne terminez par un espace"
                  value="{{ $mtdEdito['projet'] }}" class="form-input">
                </div>

                <div id="roles-container">
                    @if($mtdRoles != null)
                        @foreach ($mtdRoles as $role => $values)
                            @php
                                $formattedId = strtolower(str_replace(' ', '_', $role));
                            @endphp
                            <div class="form-field role-field"> 
                                <label for="{{ $formattedId }}" class="form-label">{{ $role }}</label>
                                <div class="role-inputs">
                                    <input type="text" id="{{ $formattedId }}" maxlength="50" name="roles[{{ $role }}]" value="{{ $values }}" class="role-input">
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <div class="form-buttons-container">
            {{-- Lien de retour vers la page video --}}
            @isset($media)
                <a href="{{ route('video.show', ['v' => $media->id]) }}" class="form-button">Retour</a>
            @else
                <a href="{{ route('home') }}" class="form-button">Retour</a>
            @endisset

            <div class="bouton-droit">
                <button type="button" id="add-role" class="form-button">Ajouter un rôle</button>
                <button type="submit" class="form-button">Confirmer</button>
            </div>
        </div>
    </form>
</div>

@endsection

@push('scripts')
    <script src="{{ asset('ressources/lib/Tagify/tagify.js') }}"></script>
    <script>
        initFormMetadonnees();
    </script>
@endpush
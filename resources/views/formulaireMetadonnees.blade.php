@extends('layouts.app')

@push('styles')
    <link href="{{ asset('ressources/Style/formulaire.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('ressources/lib/Tagify/tagify.css') }}">
@endpush

@section('content')

{{-- ATTENTION : Le bloc PHP initial a été retiré. Toutes les variables (idVideo, nomFichier, listeProfesseurs, etc.) 
    DOIVENT être passées à la vue par ton Contrôleur. --}}

<div class="form-container">
    {{-- Le action doit pointer vers ta route de mise à jour --}}
    <form method="post" action="{{ route('video.update', ['v' => $idVideo]) }}" class="metadata-form" id="metadataForm">
        @csrf
        @method('PUT') {{-- Utilisation de la méthode HTTP PUT/PATCH pour la mise à jour --}}

        <div class="form-columns">
            <div class="form-column-left">
                <div class="thumbnail-container">
                    <img src="{{ $cheminMiniatureComplet }}" alt="Miniature de la vidéo" class="thumbnail-image">
                </div>
                <h2 class="video-filename">{{ $nomFichier }}</h2>
                <h2 class="video-title">{{ $titreVideo }}</h2>

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
                <input type="hidden" name="idVideo" value="{{ $idVideo }}">

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
            <a href="{{ route('video.show', ['v' => $idVideo]) }}" class="form-button">Retour</a>

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
@extends('layouts.app')

{{-- On ajoute les styles et scripts spécifiques à cette page --}}
@push('styles')
    @vite(['resources/css/video.css', 'resources/css/menuArbo.css'])
@endpush

@section('content')
    
    {{-- ATTENTION : Le bloc PHP initial (session_start, et l'extraction des variables $idVideo, $nomFichier, etc.) 
        a été retiré. Toutes ces variables DOIVENT être passées à la vue par ton Contrôleur. --}}

    {{-- Inclusion du menu Arborescence et de la Popup --}}
    @include('menuArbo')
    @include('popup')

    <div class="contenu">
        <div class="container_principal">
            <div class="container_video">
                <div class="lecteurVideo">
                    <video class="player" id="player" data-media-id="{{ $idMedia }}" playsinline controls poster="{{ $cheminMiniatureComplet }}">
                        <source src="{{ $cheminVideoComplet }}" type="video/mp4"/>
                    </video>
                </div>
            </div>
            <div class="info_video">

                <div class ="titre_nom">
                    <div class="titre-avec-source">
                        @if($sourceVideo)
                            <span class="source-badge source-{{ $sourceVideo }}">{{ strtoupper($sourceVideo) }}</span>
                        @endif
                        <h1 class="titre" title="{{ $titreVideo }}">{{ Str::limit($titreVideo, 80) }}</h1>
                    </div>
                </div>

                <div class="container-button">
                    @auth
                        {{-- Bouton Modifier --}}
                        <a href="{{ route('medias.edit', $idMedia) }}" id="boutonModif" title="Modifier vidéo" class="btnVideo">
                            <div class="logo-btnvideo">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </div>
                            <span>Modifier</span>
                        </a>

                        {{-- Bouton Supprimer --}}
                        <form action="{{ route('medias.destroy', $idMedia) }}" method="POST" style="display: inline; margin: 0;" onsubmit="return confirm('Voulez-vous vraiment supprimer ce média ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Supprimer vidéo" class="btnVideo" id="btnSuppr">
                                <div class="logo-btnvideo">
                                    <i class="fa-solid fa-trash"></i>
                                </div>
                                <span>Supprimer</span>
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
            
            {{-- Description --}}
            @if ($description != "")
                <div class="containerDescription">
                    <p class="description">
                        {{ $description }}
                    </p>
                </div>
            @endif
            
        </div>

        {{-- Metadata container for right column --}}
        <div class="metadata-container">
            {{-- Métadonnées éditoriales --}}
            <div class="metadata_detaillee">
                <h3 style="margin-bottom: 15px; color: #333; border-bottom: 2px solid #f09520; padding-bottom: 10px;">Informations générales</h3>
                <table>
                    <tr>
                        <td><strong>Projets</strong></td>
                        <td>{{ $mtdEdito["projet"] }}</td>
                    </tr>
                    <tr>
                        <td><strong>Promotion</strong></td>
                        <td title="{{ $promotion }}">{{ $promotion ? Str::limit($promotion, 50) : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Type</strong></td>
                        <td title="{{ $type }}">{{ $type ? Str::limit($type, 50) : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Thème</strong></td>
                        <td title="{{ $theme }}">{{ $theme ? Str::limit($theme, 50) : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Professeur référent</strong></td>
                        <td>{{ $mtdEdito["professeur"] }}</td>
                    </tr>
                    {{-- NOUVEAU : Affichage des élèves --}}
<tr>
    <td><strong>Élève(s)</strong></td>
    <td>{{ $mtdEdito["eleves"] ?? 'N/A' }}</td>
</tr>

                    {{-- Rôles --}}
                    @if($mtdRoles && count($mtdRoles) > 0)
                        @foreach ($mtdRoles as $role => $values)
                            <tr>
                                <td><strong>{{ $role }}</strong></td>
                                <td>{{ $values }}</td>
                            </tr>
                        @endforeach
                    @endif
                </table>
            </div>

            {{-- Métadonnées techniques --}}
            <div class="metadata_detaillee" style="margin-top: 30px;">
                <h3 style="margin-bottom: 15px; color: #333; border-bottom: 2px solid #f09520; padding-bottom: 10px;">Informations techniques</h3>
                <table>
                    <tr>
                        <td><strong>Durée</strong></td>
                        <td>{{ $mtdTech["mtd_tech_duree"] }}</td>
                    </tr>
                    <tr>
                        <td><strong>Résolution</strong></td>
                        <td>{{ $mtdTech["mtd_tech_resolution"] }}</td>
                    </tr>
                    <tr>
                        <td><strong>Images par seconde</strong></td>
                        <td>{{ $mtdTech["mtd_tech_fps"] }} fps</td>
                    </tr>
                    <tr>
                        <td><strong>Codec vidéo</strong></td>
                        <td>{{ $mtdTech["mtd_tech_format"] }}</td>
                    </tr>
                    <tr>
                        <td><strong>Taille</strong></td>
                        <td>{{ $mtdTech["mtd_tech_taille"] }}</td>
                    </tr>
                    <tr>
                        <td><strong>Bitrate</strong></td>
                        <td>{{ $mtdTech["mtd_tech_bitrate"] }}</td>
                    </tr>

                    {{-- URIs --}}
                    <tr>
                        <td><strong>Chemin local</strong></td>
                        <td title="{{ $URIS['chemin_local'] }}">{{ $URIS['chemin_local'] ? Str::limit($URIS['chemin_local'], 40) : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>URI NAS ARCH</strong></td>
                        <td title="{{ $URIS['URI_NAS_ARCH'] }}">{{ $URIS['URI_NAS_ARCH'] !== 'N/A' ? Str::limit($URIS['URI_NAS_ARCH'], 40) : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>URI NAS PAD</strong></td>
                        <td title="{{ $URIS['URI_NAS_PAD'] }}">{{ $URIS['URI_NAS_PAD'] !== 'N/A' ? Str::limit($URIS['URI_NAS_PAD'], 40) : 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @vite(['resources/js/video.js'])
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            initLectureVideo();
            pageLectureVideo();
        });
    </script>
@endpush
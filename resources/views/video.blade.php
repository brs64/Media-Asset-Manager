@extends('layouts.app')

{{-- On ajoute les styles et scripts spécifiques à cette page --}}
@push('styles')
    <link href="{{ asset('ressources/Style/video.css') }}" rel="stylesheet">
    <link href="{{ asset('ressources/Style/menuArbo.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('ressources/lib/Plyr/plyr.css') }}" />
@endpush

@section('content')
    
    {{-- ATTENTION : Le bloc PHP initial (session_start, et l'extraction des variables $idVideo, $nomFichier, etc.) 
        a été retiré. Toutes ces variables DOIVENT être passées à la vue par ton Contrôleur. --}}

    {{-- Inclusion du menu Arborescence et de la Popup --}}
    {{-- @include('menuArbo') --}}
    @include('popup')

    <div class="contenu">
        <div class="container_principal">
            <div class="container_video">
                <div class="lecteurVideo">
                    <video class="player" id="player" playsinline controls poster="{{ $cheminMiniatureComplet }}">
                        <source src="{{ $cheminVideoComplet }}" type="video/mp4"/>
                    </video>
                </div>
            </div>
            <div class="info_video">

                <div class ="titre_nom">
                    <h1 class="titre">{{ $nomFichier }}</h1>
                    <h2>{{ $titreVideo }}</h2>
                </div>

                <div class="container-button">
                    @auth
                        {{-- Bouton Modifier --}}
                        <a href="{{ route('media.edit', $idMedia) }}" id="boutonModif" title="Modifier vidéo" class="btnVideo">
                            <div class="logo-btnvideo">
                                <img src="{{ asset('ressources/Images/modifier_video.png') }}" alt="">
                            </div>
                            <p>Modifier</p>
                        </a>

                        {{-- Bouton Supprimer --}}
                        <form action="{{ route('media.destroy', $idMedia) }}" method="POST" style="display: inline;" onsubmit="return confirm('Voulez-vous vraiment supprimer ce média ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Supprimer vidéo" class="btnVideo" id="btnSuppr">
                                <div class="logo-btnvideo">
                                    <img src="{{ asset('ressources/Images/poubelle-de-recyclage.png') }}" alt="">
                                </div>
                                <p>Supprimer</p>
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

        <div class="metadata_detaillee">
            <table>
                {{-- Métadonnées éditoriales --}}
                <tr>
                    <td><strong>Projet</strong></td>
                    <td>{{ $mtdEdito["projet"] }}</td>
                </tr>
                <tr>
                    <td><strong>Promotion</strong></td>
                    <td>{{ $promotion }}</td>
                </tr>
                <tr>
                    <td><strong>Professeur référent</strong></td>
                    <td>{{ $mtdEdito["professeur"] }}</td>
                </tr>

                {{-- Métadonnées techniques (extraites en temps réel) --}}
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
                    <td><strong>URI NAS PAD</strong></td>
                    <td>{{ $URIS['URI_NAS_PAD'] }}</td>
                </tr>
                <tr>
                    <td><strong>URI NAS MPEG</strong></td>
                    <td>{{ $URIS['URI_NAS_MPEG'] }}</td>
                </tr>
                <tr>
                    <td><strong>URI NAS ARCH</strong></td>
                    <td>{{ $URIS['URI_NAS_ARCH'] }}</td>
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
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('ressources/lib/Plyr/plyr.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            initLectureVideo();
            pageLectureVideo();
        });
    </script>
@endpush
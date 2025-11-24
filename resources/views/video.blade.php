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
    @include('menuArbo')
    @include('popup')

    <div class="contenu">
        <div class="container_principal">
            <div class="container_video">
                <div class="lecteurVideo">
                    <video class="player" id="player" playsinline controls data-poster="{{ $cheminMiniatureComplet }}">
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
                    {{-- Bouton Télécharger --}}
                    @if (!empty($cheminCompletNAS_ARCH))
                        <button title="Télécharger vidéo" class="btnVideo" onclick="changerTitrePopup('Téléchargement'); 
                            changerTextePopup('Voulez-vous télécharger la vidéo {{ $nomFichier }} ?');
                            changerTexteBtn('Confirmer', 'btn1');
                            changerTexteBtn('Annuler', 'btn2');
                            attribuerFonctionBtn('lancerTelechargement','{{ $cheminCompletNAS_ARCH }}', 'btn1');
                            afficherBtn('btn2');
                            cacherBtn('btn3');
                            afficherPopup();">
                            <div class="logo-btnvideo">
                                <img src="{{ asset('ressources/Images/télécharger_image.png') }}" alt="">
                            </div>
                            <p>Télécharger</p>
                        </button>
                        <div id="overlay" style="display : none">
                            <div class="loader"></div>
                            <p>Téléchargement en cours. Veuillez rafraîchir la page à la fin du téléchargement</p>
                        </div>
                    @else
                        <button title="Télécharger vidéo" class="btnVideo boutonGrise">
                            <div class="logo-btnvideo">
                                <img src="{{ asset('ressources/Images/télécharger_image.png') }}" alt="">
                            </div>
                            <p>Indisponible</p>
                        </button>
                    @endif

                    {{-- Bouton Modifier --}}
                    @if (controleurVerifierAcces(ACCES_MODIFICATION))
                        <button id="boutonModif" title="Modifier vidéo" class="btnVideo"onclick="window.location.href='{{ route('formulaireMetadonnees', ['v' => $idVideo]) }}';">
                            <div class="logo-btnvideo">
                                <img src="{{ asset('ressources/Images/modifier_video.png') }}" alt="">
                            </div>
                            <p>Modifier</p>
                        </button>
                    @endif

                    {{-- Bouton Supprimer --}}
                    @if (controleurVerifierAcces(ACCES_SUPPRESSION))
                        <button title="Supprimer vidéo" class="btnVideo" id="btnSuppr" onclick="  changerTitrePopup('Suppression'); 
                            changerTextePopup('De quel espace voulez-vous supprimer la vidéo {{ $nomFichier }} ?');
                            changerTexteBtn('Base de données', 'btn1');
                            changerTexteBtn('NAS PAD', 'btn2');
                            changerTexteBtn('NAS Archive', 'btn3');
                            attribuerFonctionBtn('supprimerVideo','{{ $idVideo }}, local', 'btn1');
                            attribuerFonctionBtn('supprimerVideo','{{ $idVideo }}, PAD', 'btn2');
                            attribuerFonctionBtn('supprimerVideo','{{ $idVideo }}, ARCH', 'btn3');
                            afficherBtn('btn2');
                            afficherBtn('btn3');
                            afficherPopup();">
                            <div class="logo-btnvideo">
                                <img src="{{ asset('ressources/Images/poubelle-de-recyclage.png') }}" alt="">
                            </div>
                            <p>Supprimer</p>
                        </button>
                    @endif

                    {{-- Bouton Diffuser --}}
                    @if(controleurVerifierAcces(ACCES_DIFFUSION))
                        @if (!empty($cheminCompletNAS_PAD))
                            <button id="boutonDiffusion" title="Diffuser vidéo" class="btnVideo" onclick="  changerTitrePopup('Diffusion'); 
                                changerTextePopup('Voulez-vous diffuser la vidéo {{ $nomFichier }} ?');
                                changerTexteBtn('Confirmer', 'btn1');
                                changerTexteBtn('Annuler', 'btn2');
                                attribuerFonctionBtn('lancerDiffusion','{{ $cheminCompletNAS_PAD }}', 'btn1');
                                afficherBtn('btn2');
                                cacherBtn('btn3');
                                afficherPopup();">
                                <div class="logo-btnvideo">
                                    <img src="{{ asset('ressources/Images/diffuser.png') }}" alt="">
                                </div>
                                <p>Diffuser</p>
                            </button>
                        @else
                            <button id="boutonDiffusion" title="Diffuser vidéo" class="btnVideo boutonGrise">
                                <div class="logo-btnvideo">
                                    <img src="{{ asset('ressources/Images/diffuser.png') }}" alt="">
                                </div>
                                <p>Indisponible</p>
                            </button>
                        @endif
                    @endif
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
                {{-- Affichage des métadonnées statiques --}}
                @php
                $metadata = [
                    "URI du NAS PAD" => $URIS['URI_NAS_PAD'],
                    "URI du NAS ARCH" => $URIS['URI_NAS_ARCH'],
                    "Durée" => $mtdTech["mtd_tech_duree"],
                    "Image par seconde" => $mtdTech["mtd_tech_fps"] . " fps",
                    "Résolution" => $mtdTech["mtd_tech_resolution"],
                    "Format" => $mtdTech["mtd_tech_format"],
                    "Projet" => $mtdEdito["projet"],
                    "Promotion" => $promotion,
                    "Professeur référent" => $mtdEdito["professeur"]
                ];
                @endphp
                @foreach ($metadata as $key => $value)
                    <tr>
                        <td><strong>{{ $key }}</strong></td>
                        <td>{{ $value }}</td>
                    </tr>
                @endforeach

                {{-- Affichage des rôles --}}
                @if($mtdRoles!=null)
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
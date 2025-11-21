@extends('layouts.app')

{{-- Suppression des balises HTML structurelles (html, head, body) car elles sont gérées par layouts/app.blade.php --}}

{{-- On ajoute les styles et scripts spécifiques à cette page --}}
@push('styles')
    <link href="{{ asset('ressources/Style/menuArbo.css') }}" rel="stylesheet">
    <link href="{{ asset('ressources/Style/home.css') }}" rel="stylesheet">
    <link href="{{ asset('ressources/lib/Swiper/swiper-bundle.min.css') }}" rel="stylesheet">
@endpush

@section('content')

    {{-- ATTENTION : Le bloc PHP initial doit être retiré. Les variables $tabVideos et $tabDernierProjet 
        DOIVENT être passées à la vue par ton Contrôleur. --}}

    {{-- Inclusion du menu Arborescence (anciennement menuArbo.php) --}}
    @include('menuArbo')

    <div class="container">
        <div class="sliderVideo">
            <h2>Vos vidéos</h2>
            <div class="swiperVideo">
                <div class="swiper-wrapper">
                    @foreach ($tabVideos as $video)
                        <div class='swiper-slide'>
                            {{-- Utilisation de route() pour les liens, en gardant l'id $video['id'] --}}
                            <a href="{{ route('video.show', ['v' => $video['id']]) }}">
                                <div class='miniature'>
                                    <img src="{{ asset($video['cheminMiniatureComplet']) }}" alt='Miniature de la vidéo' class='imageMiniature'/>
                                </div>
                                <h3>{{ $video['titre'] }}</h3>
                                <h4>{{ $video['titreVideo'] }}</h4>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
        
        {{-- Logique pour afficher le slider du dernier projet --}}
        @if (!empty($tabDernierProjet))
            <div class="sliderVideoProjet">
            <h2>{{ $tabDernierProjet[0]["projet"] }}</h2>
            <div class="swiperVideo">
                <div class="swiper-wrapper">
                    @foreach ($tabDernierProjet as $video)
                        <div class='swiper-slide'>
                            <a href="{{ route('video.show', ['v' => $video['id']]) }}">
                                <div class='miniature'>
                                    <img src="{{ asset($video['cheminMiniatureComplet']) }}" alt='Miniature de la vidéo' class='imageMiniature'/>
                                </div>
                                <h3>{{ $video['titre'] }}</h3>
                                <h4>{{ $video['titreVideo'] }}</h4>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="swiper-projet-button-next"></div>
            <div class="swiper-projet-button-prev"></div>
        </div>
        @endif
        
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('ressources/lib/Swiper/swiper-bundle.min.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            initCarrousel();
        });
    </script>
@endpush
{{-- ATTENTION : Le <head> et <body> ont été retirés et DOIVENT être dans layouts/app.blade.php --}}

@push('styles')
    <link href="{{ asset('resources/css/header.css') }}" rel="stylesheet">
@endpush

<header>
    <div class="container">
        {{-- Utilisation de route() pour les liens --}}
        <a href="{{ route('home') }}">
            <div class="logo-bts">
                <img src="{{ asset('/images/logo_BTS_Play.png') }}" alt="logo">
            </div>
        </a>

        <form class="recherche" action="{{ route('search') }}" method="GET">
            <input type="search" name="motCle" placeholder="Rechercher une vidéo...">
            <button type="submit">
                <div class="logo-search">
                    <img src="{{ asset('/images/recherche.png') }}" alt="Rechercher">
                </div>
            </button>
        </form>
        
        <div class="compte">
            {{-- Traduction des conditions PHP en directives Blade --}}
            @if(!\Auth::check())
                <a href="{{ route('login') }}">
                    Se connecter
                    <div class="logo-compte">
                        <img src="{{ asset('/images/account.png') }}" alt="Compte">
                    </div>
                </a>
            @else
                <a class="btnSousMenu" href="{{ route('profile.edit') }}">
                    {{ \Auth::user()->name }}
                    <div class="logo-compte">
                        <img src="{{ asset('/images/account.png') }}" alt="Compte">
                    </div>
                </a>
                <div class="sousMenu">

                    <a href="{{ route('admin.dashboard') }}">
                        <img class='iconeSousMenu' src='{{ asset('/images/Parametre.png') }}'>
                        Paramétrer
                    </a>

                    <a href="{{ asset('docs/html/index.html') }}">
                        <img class='iconeSousMenu' src='{{ asset('/images/documentation.png') }}'>
                        Documentation
                    </a>

                    <a href="{{ route('logout') }}" >
                        <img class='iconeSousMenu'src='{{ asset('/images/logout.png') }}'>
                        Se déconnecter
                    </a>

                </div>
            @endif
        </div>
    </div>
</header>

{{-- Déplacement du script d'initialisation dans le stack 'scripts' --}}
@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    affichageSousMenu();
});
</script>
@endpush
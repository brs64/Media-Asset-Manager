{{-- ATTENTION : Le <head> et <body> ont été retirés et DOIVENT être dans layouts/app.blade.php --}}

@push('styles')
    <link href="{{ asset('resources/css/header.css') }}" rel="stylesheet">
@endpush

<header>
    <div class="container">
        {{-- Utilisation de route() pour les liens --}}
        <a href="{{ route('home') }}">
            <div class="logo-bts">
                <img src="{{ asset('public/images/logo_BTS_Play.png') }}" alt="logo">
            </div>
        </a>

        <form class="recherche" action="{{ route('search') }}" method="GET">
            <input type="search" name="motCle" placeholder="Rechercher une vidéo...">
            <button type="submit">
                <div class="logo-search">
                    <img src="{{ asset('public/images/recherche.png') }}" alt="Rechercher">
                </div>
            </button>
        </form>
        
        <div class="compte">
            {{-- Traduction des conditions PHP en directives Blade --}}
            @if(!session('loginUser'))
                <a href="{{ route('login') }}">
                    Se connecter
                    <div class="logo-compte">
                        <img src="{{ asset('public/images/account.png') }}" alt="Compte">
                    </div>
                </a>
            @else
                <a class="btnSousMenu" onclick="affichageSousMenu()">
                    {{ session('loginUser') }}
                    <div class="logo-compte">
                        <img src="{{ asset('public/images/account.png') }}" alt="Compte">
                    </div>
                </a>
                <div class="sousMenu">

                    @php
                    // Nous conservons les fonctions/constantes comme ACCES_ADMINISTRATION
                    if(controleurVerifierAcces(ACCES_ADMINISTRATION)){
                    @endphp
                    
                    <a href="{{ route('admin.dashboard') }}">
                        <img class='iconeSousMenu' src='{{ asset('public/images/Parametre.png') }}'>
                        Paramétrer
                    </a>
                    
                    @php
                    // Nous devons traduire la vérification de file_exists en utilisant public_path()
                    if(session('role') == ROLE_ADMINISTRATEUR && file_exists(public_path('docs/html/index.html'))){
                    @endphp
                    
                    <a href="{{ asset('docs/html/index.html') }}">
                        <img class='iconeSousMenu' src='{{ asset('public/images/documentation.png') }}'>
                        Documentation
                    </a>
                    @php } } @endphp

                    <a href="{{ route('logout') }}" >
                        <img class='iconeSousMenu'src='{{ asset('public/images/logout.png') }}'>
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
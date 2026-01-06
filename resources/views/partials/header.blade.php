{{-- ATTENTION : Le <head> et <body> ont été retirés et DOIVENT être dans layouts/app.blade.php --}}

@push('styles')
    @vite(['resources/css/header.css'])
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
        
        <div class="relative h-full flex items-center ml-4 group z-50">

            {{-- 1. NOT LOGGED IN --}}
            @if(!\Auth::check())
                <a href="{{ route('login') }}" class="flex items-center gap-2 text-white! hover:text-[#f09520] font-bold transition duration-200">
                    Se connecter
                    <div class="w-8 h-8">
                        <img src="{{ asset('/images/account.png') }}" alt="Compte" class="w-full h-full object-contain brightness-0 invert">
                    </div>
                </a>

            {{-- 2. LOGGED IN --}}
            @else
                <button class="flex items-center gap-2 cursor-pointer focus:outline-none text-white group-hover:text-[#f09520] transition duration-200 py-4 bg-transparent border-0">
                    <span class="font-bold text-base">{{ \Auth::user()->name }}</span>
                    
                    <div class="w-8 h-8 min-w-8">
                        <img src="{{ asset('/images/account.png') }}" alt="Compte" class="w-full h-full object-contain brightness-0 invert group-hover:invert-0 group-hover:brightness-100 transition-all">
                    </div>
                </button>

                <div class="hidden group-hover:block absolute top-[80%] right-0 w-56 pt-2">
                    
                    <div class="bg-white rounded-md shadow-xl border border-gray-100 overflow-hidden text-left">
                        
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700! hover:bg-orange-50 hover:text-[#f09520]! transition-colors no-underline">
                            <img class='w-5! h-5! object-contain' src='{{ asset('/images/Parametre.png') }}'>
                            <span>Administration</span>
                        </a>

                        <div class="border-t border-gray-100"></div>

                        <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                            @csrf
                            <button type="submit" class="w-full text-left flex items-center gap-3 px-4 py-3 text-sm text-red-600! hover:bg-red-50 transition-colors bg-transparent border-0 cursor-pointer">
                                <img class='w-5! h-5! object-contain' src='{{ asset('/images/logout.png') }}'>
                                <span>Se déconnecter</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</header>

@push('scripts')

@endpush
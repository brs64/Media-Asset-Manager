@extends('layouts.app')

{{-- Ajout des styles spécifiques à cette page d'erreur --}}
@push('styles')
    @vite(['resources/css/transfert.css'])
@endpush

@section('content')

    <div class="container" style="padding: 50px; text-align: center;">
        
        {{-- En Laravel, le code d'erreur est souvent passé directement par le framework ou le contrôleur. 
             Nous supposons ici que la variable $code est passée à la vue. --}}
        
        @php
            $message = "Une erreur inconnue est survenue. Veuillez contacter votre administrateur.";
            
            // Logique de ton ancien switch/case conservée dans un bloc PHP pour la conversion directe
            switch ($code ?? null) {
                case 404:
                    $message = "Erreur 404 : La ressource demandée est introuvable.";
                    break;
                case 403:
                    $message = "Erreur 403 : Accès refusé.";
                    break;
                case 415:
                    $message = "Erreur 415 : Le fichier n'a pas encore été transféré. Veuillez contacter votre administrateur.";
                    break;
                case 500:
                    $message = "Erreur 500 : Erreur interne du serveur. Veuillez contacter votre administrateur.";
                    break;
                default:
                    // Le message par défaut est déjà défini
                    break;
            }
        @endphp

        <h1>Erreur {{ $code ?? '' }}</h1>

        <p>{{ $message }}</p>
        
        <br>
        <a href="{{ route('home') }}" class="btn">Retour à l'accueil</a>
    </div>

@endsection
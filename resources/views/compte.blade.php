@extends('layouts.app')

{{-- On ajoute le CSS spécifique à cette page (compte.css) --}}
@push('styles')
    @vite(['resources/css/compte.css'])
@endpush

@section('content')

<div class="login-container">
    {{-- J'ai gardé action="#" pour l'instant, mais tu devras mettre ta route Laravel ici plus tard --}}
    <form method="POST" action="#">
        
        @csrf {{-- PROTECTION OBLIGATOIRE LARAVEL (remplace ton ancienne sécurité) --}}
        
        <img class="userIcon" src="{{ asset('ressources/Images/user.png') }}">
        <input type="hidden" name="action" value="connexionUtilisateur">
        
        <p>Nom d'utilisateur :</p>
        <input type="text" name="loginUser">
        
        <p>Mot de passe :</p>
        <input type="password" name="passwordUser">
        
        <button type="submit" class="btn">Confirmer</button>
    </form>
</div>

@endsection
@extends('layouts.documentation')

@section('content')
<div class="docs-breadcrumb">
    <a href="{{ route('home') }}">Accueil</a>
    <span>/</span>
    <span>Documentation</span>
</div>

<h1 class="docs-page-title">Documentation BTSPlay</h1>

<div class="docs-section">
    <p>
        Bienvenue dans la documentation de <strong>BTSPlay</strong>, le gestionnaire de médias audiovisuels (M.A.M.)
        du BTS Audiovisuel de Biarritz. Cette plateforme permet de gérer, consulter et partager les projets
        vidéo réalisés par les étudiants.
    </p>
</div>

<div class="docs-section">
    <h2>À propos de BTSPlay</h2>
    <p>
        BTSPlay est une application web qui centralise l'accès aux projets vidéo stockés sur deux serveurs NAS
        du Pôle son et image de Biarritz. L'application offre :
    </p>
    <ul>
        <li><strong>Streaming vidéo</strong> : Visionnez les projets directement depuis votre navigateur</li>
        <li><strong>Gestion des métadonnées</strong> : Titre, description, participants, thématiques</li>
        <li><strong>Recherche avancée</strong> : Trouvez rapidement les vidéos par mots-clés</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Structure de la documentation</h2>
    <p>
        Cette documentation est organisée en trois sections principales :
    </p>

    <h3>1. Guide de démarrage</h3>
    <p>
        Premiers pas avec BTSPlay : connexion, navigation de base et fonctionnalités principales.
    </p>

    <h3>2. Interface utilisateur</h3>
    <p>
        Description détaillée de toutes les fonctionnalités de l'application :
    </p>
    <ul>
        <li><strong>Page d'accueil</strong> : Découvrir et parcourir les vidéos</li>
        <li><strong>Barre de navigation</strong> : Se déplacer dans l'application</li>
        <li><strong>Lecteur vidéo</strong> : Visionner et interagir avec les contenus</li>
    </ul>
</div>

<div class="docs-navigation-buttons">
    <div></div>
    <a href="{{ route('docs.getting-started') }}" class="docs-nav-button">
        Guide de démarrage
        <i class="fa-solid fa-arrow-right"></i>
    </a>
</div>
@endsection

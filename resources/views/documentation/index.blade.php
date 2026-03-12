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
        <li><strong>Gestion des droits</strong> : Accès différencié pour étudiants et professeurs</li>
        <li><strong>Administration</strong> : Outils de synchronisation et de transcodage</li>
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
        Description détaillée de toutes les fonctionnalités accessibles aux étudiants et professeurs :
    </p>
    <ul>
        <li><strong>Page d'accueil</strong> : Découvrir et parcourir les vidéos</li>
        <li><strong>Barre de navigation</strong> : Se déplacer dans l'application</li>
        <li><strong>Lecteur vidéo</strong> : Visionner et interagir avec les contenus</li>
        <li><strong>Recherche</strong> : Trouver des vidéos spécifiques</li>
    </ul>

    <h3>3. Administration</h3>
    <p>
        Guide complet des outils d'administration réservés aux professeurs :
    </p>
    <ul>
        <li><strong>Vue d'ensemble</strong> : Introduction au panneau d'administration</li>
        <li><strong>Base de données</strong> : Synchronisation des médias depuis les NAS</li>
        <li><strong>Transferts</strong> : Gestion du transcodage vidéo</li>
        <li><strong>Réconciliation</strong> : Vérification de la cohérence entre les NAS</li>
        <li><strong>Paramètres</strong> : Configuration de l'application</li>
        <li><strong>Gestion des utilisateurs</strong> : Création et administration des comptes</li>
    </ul>
</div>

<div class="docs-note">
    <p>
        <strong>Note :</strong> Cette documentation est destinée aux utilisateurs de BTSPlay.
        Pour des informations techniques sur l'installation et le déploiement, consultez le fichier
        <code>README.md</code> à la racine du projet.
    </p>
</div>

<div class="docs-section">
    <h2>Rôles et permissions</h2>
    <p>
        BTSPlay gère trois types d'utilisateurs :
    </p>
    <ul>
        <li><strong>Visiteurs</strong> : Peuvent consulter les vidéos publiques sans authentification</li>
        <li><strong>Étudiants</strong> : Accès complet aux vidéos et métadonnées</li>
        <li><strong>Professeurs</strong> : Tous les droits étudiants + accès au panneau d'administration</li>
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

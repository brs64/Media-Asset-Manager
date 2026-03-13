@extends('layouts.documentation')

@section('content')
<div class="docs-breadcrumb">
    <a href="{{ route('home') }}">Accueil</a>
    <span>/</span>
    <a href="{{ route('docs.index') }}">Documentation</a>
    <span>/</span>
    <span>Guide de démarrage</span>
</div>

<h1 class="docs-page-title">Guide de démarrage</h1>

<div class="docs-section">
    <p>
        Ce guide vous accompagne dans vos premiers pas avec BTSPlay. Vous découvrirez comment accéder
        à la plateforme, naviguer dans l'interface et utiliser les fonctionnalités de base.
    </p>
</div>

<div class="docs-section">
    <h2>Accès à la plateforme</h2>

    <h3>Connexion</h3>
    <p>
        Pour accéder à BTSPlay, rendez-vous sur l'URL fournie par votre établissement. Deux modes d'accès
        sont disponibles :
    </p>
    <ul>
        <li><strong>Mode visiteur</strong> : Consultation libre des vidéos publiques sans authentification</li>
        <li><strong>Mode authentifié</strong> : Accès complet nécessitant un compte utilisateur</li>
    </ul>

    <h3>Se connecter avec un compte</h3>
    <p>
        Pour vous connecter :
    </p>
    <ul>
        <li>Cliquez sur le bouton <strong>"Se connecter"</strong> en haut à droite de l'écran</li>
        <li>Saisissez votre adresse email et votre mot de passe</li>
        <li>Cliquez sur <strong>"Connexion"</strong></li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Première connexion ?</strong> Les comptes étudiants sont créés par les professeurs.
            Si vous n'avez pas encore de compte, contactez votre enseignant.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Découvrir l'interface</h2>

    <h3>La page d'accueil</h3>
    <p>
        Après connexion, vous arrivez sur la page d'accueil qui présente une grille de vidéos.
        Chaque vignette affiche :
    </p>
    <ul>
        <li><strong>Miniature</strong> : Aperçu visuel du projet</li>
        <li><strong>Titre</strong> : Nom du projet</li>
        <li><strong>Description</strong> : Résumé du contenu</li>
        <li><strong>Promotion</strong> : Année de réalisation</li>
    </ul>

    <h3>La barre de navigation</h3>
    <p>
        En haut de l'écran, la barre de navigation vous permet de :
    </p>
    <ul>
        <li><strong>Revenir à l'accueil</strong> : Cliquez sur le logo BTSPlay</li>
        <li><strong>Rechercher</strong> : Utilisez la barre de recherche centrale</li>
        <li><strong>Accéder à votre profil</strong> : Menu utilisateur à droite</li>
        <li><strong>Administration</strong> : Panneau admin (professeurs uniquement)</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Visionner une vidéo</h2>
    <p>
        Pour regarder une vidéo :
    </p>
    <ul>
        <li>Cliquez sur une vignette dans la grille d'accueil</li>
        <li>La page de détail s'ouvre avec le lecteur vidéo intégré</li>
        <li>Utilisez les contrôles du lecteur pour :
            <ul style="padding-left: 2rem; margin-top: 0.5rem;">
                <li>Lancer/mettre en pause la lecture</li>
                <li>Ajuster le volume</li>
                <li>Passer en mode plein écran</li>
                <li>Modifier la qualité de lecture</li>
            </ul>
        </li>
    </ul>

    <h3>Informations sur la vidéo</h3>
    <p>
        Sous le lecteur, vous trouverez :
    </p>
    <ul>
        <li><strong>Métadonnées</strong> : Description complète, type de projet, thématiques</li>
        <li><strong>Participants</strong> : Liste des étudiants ayant contribué au projet avec leurs rôles</li>
        <li><strong>Projets associés</strong> : Liens vers d'autres vidéos du même projet</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Rechercher des vidéos</h2>
    <p>
        BTSPlay propose plusieurs méthodes de recherche :
    </p>

    <h3>Recherche rapide</h3>
    <ul>
        <li>Saisissez des mots-clés dans la barre de recherche en haut de page</li>
        <li>L'autocomplétion vous suggère des résultats en temps réel</li>
        <li>Appuyez sur Entrée ou cliquez sur la loupe pour lancer la recherche</li>
    </ul>

    <h3>Critères de recherche</h3>
    <p>
        La recherche porte sur :
    </p>
    <ul>
        <li><strong>Titres</strong> : Noms des projets</li>
        <li><strong>Descriptions</strong> : Contenu des métadonnées</li>
        <li><strong>Promotions</strong> : Années de réalisation</li>
        <li><strong>Thématiques</strong> : Catégories des projets</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Gérer son profil</h2>
    <p>
        Depuis le menu utilisateur (icône profil en haut à droite) :
    </p>
    <ul>
        <li><strong>Profil</strong> : Modifier vos informations personnelles et mot de passe</li>
        <li><strong>Administration</strong> : Accéder au panneau admin (professeurs)</li>
        <li><strong>Se déconnecter</strong> : Terminer votre session</li>
    </ul>
</div>

<div class="docs-warning">
    <p>
        <strong>Sécurité :</strong> Pensez à vous déconnecter après chaque session, surtout sur
        un ordinateur partagé.
    </p>
</div>

<div class="docs-section">
    <h2>Prochaines étapes</h2>
    <p>
        Maintenant que vous maîtrisez les bases, explorez la documentation détaillée :
    </p>
    <ul>
        <li><strong>Interface utilisateur</strong> : Découvrez toutes les fonctionnalités en détail</li>
        <li><strong>Administration</strong> : Guide complet pour les professeurs</li>
    </ul>
</div>

<div class="docs-navigation-buttons">
    <a href="{{ route('docs.index') }}" class="docs-nav-button">
        <i class="fa-solid fa-arrow-left"></i>
        Documentation
    </a>
    <a href="{{ route('docs.interface.home') }}" class="docs-nav-button">
        Interface - Page d'accueil
        <i class="fa-solid fa-arrow-right"></i>
    </a>
</div>
@endsection

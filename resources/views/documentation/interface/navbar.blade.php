@extends('layouts.documentation')

@section('content')
<div class="docs-breadcrumb">
    <a href="{{ route('home') }}">Accueil</a>
    <span>/</span>
    <a href="{{ route('docs.index') }}">Documentation</a>
    <span>/</span>
    <span>Interface</span>
    <span>/</span>
    <span>Barre de navigation</span>
</div>

<h1 class="docs-page-title">Barre de navigation</h1>

<div class="docs-section">
    <p>
        La barre de navigation est l'élément permanent de l'interface BTSPlay. Située en haut de chaque page,
        elle reste fixe lors du défilement et donne accès aux fonctions essentielles de l'application.
    </p>
</div>

<div class="docs-section">
    <h2>Structure de la barre</h2>
    <p>
        La barre de navigation se divise en trois zones principales :
    </p>

    <h3>Zone gauche : Logo</h3>
    <ul>
        <li><strong>Logo BTSPlay</strong> : Permet de revenir à la page d'accueil depuis n'importe quelle page</li>
        <li><strong>Cliquable en permanence</strong> : Fonctionne comme un lien de retour rapide</li>
        <li><strong>Identité visuelle</strong> : Rappelle constamment l'application utilisée</li>
    </ul>

    <h3>Zone centrale : Recherche</h3>
    <ul>
        <li><strong>Barre de recherche</strong> : Champ de saisie pour rechercher des vidéos</li>
        <li><strong>Autocomplétion</strong> : Suggestions en temps réel pendant la saisie</li>
        <li><strong>Bouton de recherche</strong> : Icône loupe pour lancer la recherche</li>
        <li><strong>Toujours accessible</strong> : Disponible sur toutes les pages</li>
    </ul>

    <h3>Zone droite : Menu utilisateur</h3>
    <ul>
        <li><strong>État de connexion</strong> : Affiche "Se connecter" ou le nom d'utilisateur</li>
        <li><strong>Icône profil</strong> : Représentation visuelle de l'utilisateur connecté</li>
        <li><strong>Menu déroulant</strong> : S'ouvre au survol/clic pour révéler les options</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Menu utilisateur non connecté</h2>
    <p>
        Lorsque vous n'êtes pas connecté, la zone droite affiche simplement :
    </p>
    <ul>
        <li><strong>Bouton "Se connecter"</strong> : Redirige vers la page de connexion</li>
        <li><strong>Icône utilisateur</strong> : Symbole Font Awesome fa-user</li>
        <li><strong>Effet de survol</strong> : Changement de couleur vers l'orange BTSPlay</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Menu utilisateur connecté</h2>
    <p>
        Une fois authentifié, le menu déroulant propose plusieurs options :
    </p>

    <h3>En-tête du menu</h3>
    <ul>
        <li><strong>Nom d'utilisateur</strong> : Affiché en gras</li>
        <li><strong>Icône utilisateur</strong> : Symbole de profil</li>
        <li><strong>Effet de survol</strong> : Le menu s'ouvre automatiquement</li>
    </ul>

    <h3>Options du menu</h3>

    <h3>1. Profil</h3>
    <ul>
        <li><strong>Icône</strong> : Engrenage (fa-gear)</li>
        <li><strong>Fonction</strong> : Permet de modifier les informations personnelles</li>
        <li><strong>Accès</strong> : Tous les utilisateurs connectés</li>
        <li><strong>Actions possibles</strong> :
            <ul style="padding-left: 2rem; margin-top: 0.5rem;">
                <li>Modifier le nom d'affichage</li>
                <li>Changer l'adresse email</li>
                <li>Modifier le mot de passe</li>
                <li>Supprimer le compte (avec confirmation)</li>
            </ul>
        </li>
    </ul>

    <h3>2. Administration</h3>
    <ul>
        <li><strong>Icône</strong> : Outils croisés (fa-screwdriver-wrench)</li>
        <li><strong>Fonction</strong> : Accéder au panneau d'administration</li>
        <li><strong>Accès</strong> : Professeurs uniquement</li>
        <li><strong>Destination</strong> : Redirige vers l'onglet "Base de données" du panneau admin</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Note :</strong> L'option "Administration" n'apparaît que pour les comptes professeurs.
            Les étudiants ne voient pas cette entrée dans le menu.
        </p>
    </div>

    <h3>3. Se déconnecter</h3>
    <ul>
        <li><strong>Icône</strong> : Flèche de sortie (fa-right-from-bracket)</li>
        <li><strong>Fonction</strong> : Termine la session et déconnecte l'utilisateur</li>
        <li><strong>Accès</strong> : Tous les utilisateurs connectés</li>
        <li><strong>Style</strong> : Texte rouge pour indiquer une action de sortie</li>
        <li><strong>Sécurité</strong> : Utilise un formulaire POST avec token CSRF</li>
    </ul>

    <div class="docs-warning">
        <p>
            <strong>Sécurité :</strong> Pensez toujours à vous déconnecter après utilisation, surtout
            sur un ordinateur partagé ou public. La déconnexion invalide immédiatement votre session.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Comportement de la barre</h2>

    <h3>Position fixe</h3>
    <ul>
        <li><strong>Toujours visible</strong> : La barre reste en haut de l'écran lors du défilement</li>
        <li><strong>Z-index élevé</strong> : S'affiche au-dessus du contenu de la page</li>
        <li><strong>Hauteur standard</strong> : 80 pixels sur tous les écrans</li>
    </ul>

    <h3>Responsive Design</h3>
    <ul>
        <li><strong>Desktop</strong> : Tous les éléments alignés horizontalement</li>
        <li><strong>Tablette</strong> : Légère réduction de la taille de la barre de recherche</li>
        <li><strong>Mobile</strong> : Adaptation pour écrans étroits (à venir)</li>
    </ul>

    <h3>Animations</h3>
    <ul>
        <li><strong>Menu déroulant</strong> : Transition fluide à l'ouverture/fermeture</li>
        <li><strong>Survol des liens</strong> : Changement de couleur progressif</li>
        <li><strong>Icônes</strong> : Effet de surbrillance au survol</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Raccourcis clavier (à venir)</h2>
    <div class="docs-warning">
        <p>
            <strong>Fonctionnalité en développement :</strong> Dans les versions futures, des raccourcis
            clavier seront disponibles pour accéder rapidement aux fonctions de la barre de navigation
            (ex: Ctrl+K pour la recherche).
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Conseils d'utilisation</h2>

    <h3>Navigation rapide</h3>
    <p>
        Utilisez le logo BTSPlay comme point de retour permanent. Peu importe où vous êtes dans
        l'application, un clic sur le logo vous ramène à la grille d'accueil.
    </p>

    <h3>Recherche contextuelle</h3>
    <p>
        La barre de recherche est toujours accessible. Vous n'avez pas besoin de revenir à l'accueil
        pour lancer une recherche.
    </p>

    <h3>Menu utilisateur</h3>
    <p>
        Le menu s'ouvre au survol de la souris. Pour les utilisateurs tactiles (tablettes), un simple
        tap sur l'icône utilisateur ouvre le menu.
    </p>
</div>

<div class="docs-navigation-buttons">
    <a href="{{ route('docs.interface.home') }}" class="docs-nav-button">
        <i class="fa-solid fa-arrow-left"></i>
        Page d'accueil
    </a>
    <a href="{{ route('docs.interface.video-player') }}" class="docs-nav-button">
        Lecteur vidéo
        <i class="fa-solid fa-arrow-right"></i>
    </a>
</div>
@endsection

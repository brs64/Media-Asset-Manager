@extends('layouts.documentation')

@section('content')
<div class="docs-breadcrumb">
    <a href="{{ route('home') }}">Accueil</a>
    <span>/</span>
    <a href="{{ route('docs.index') }}">Documentation</a>
    <span>/</span>
    <span>Interface</span>
    <span>/</span>
    <span>Page d'accueil</span>
</div>

<h1 class="docs-page-title">Page d'accueil</h1>

<div class="docs-section">
    <p>
        La page d'accueil de BTSPlay est le point central de navigation de l'application. Elle présente
        l'ensemble des vidéos disponibles sous forme de grille interactive, permettant un accès rapide
        aux contenus.
    </p>
</div>

<div class="docs-section">
    <h2>Structure de la page</h2>

    <h3>En-tête</h3>
    <p>
        L'en-tête fixe reste visible en permanence lors du défilement. Il contient :
    </p>
    <ul>
        <li><strong>Logo BTSPlay</strong> : Permet de revenir à la page d'accueil depuis n'importe où</li>
        <li><strong>Barre de recherche</strong> : Recherche rapide avec autocomplétion</li>
        <li><strong>Menu utilisateur</strong> : Accès au profil, administration et déconnexion</li>
    </ul>

    <h3>Grille de vidéos</h3>
    <p>
        Le corps de la page affiche les vidéos en grille responsive :
    </p>
    <ul>
        <li><strong>16 vidéos par page</strong> : Pagination automatique pour les grandes collections</li>
        <li><strong>Disposition adaptative</strong> : La grille s'adapte à la taille de votre écran</li>
        <li><strong>Tri chronologique</strong> : Les vidéos les plus récentes apparaissent en premier</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Les vignettes vidéo</h2>
    <p>
        Chaque vignette présente les informations essentielles d'un projet :
    </p>

    <h3>Miniature</h3>
    <ul>
        <li><strong>Image de prévisualisation</strong> : Générée automatiquement depuis la vidéo</li>
        <li><strong>Effet de survol</strong> : La vignette se met en surbrillance au passage de la souris</li>
        <li><strong>Indicateur de lecture</strong> : Icône play visible au survol</li>
    </ul>

    <h3>Métadonnées visibles</h3>
    <ul>
        <li><strong>Titre</strong> : Nom du projet en gras</li>
        <li><strong>Description</strong> : Résumé court (tronqué si trop long)</li>
        <li><strong>Promotion</strong> : Année de réalisation (ex: "2023-2024")</li>
        <li><strong>Type de projet</strong> : Fiction, documentaire, reportage, etc.</li>
        <li><strong>Projets associés</strong> : Tags des projets liés</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Conseil :</strong> Passez la souris sur une vignette pour voir l'effet de surbrillance
            et l'icône de lecture. Cliquez n'importe où sur la vignette pour ouvrir la vidéo.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Navigation dans la grille</h2>

    <h3>Pagination</h3>
    <p>
        Lorsque plus de 16 vidéos sont disponibles, la pagination apparaît en bas de page :
    </p>
    <ul>
        <li><strong>Numéros de pages</strong> : Cliquez sur un numéro pour accéder à cette page</li>
        <li><strong>Page courante</strong> : Mise en évidence avec une couleur différente</li>
        <li><strong>Flèches de navigation</strong> : Précédent/Suivant pour parcourir les pages</li>
    </ul>

    <h3>Chargement des vidéos</h3>
    <p>
        Les vidéos sont chargées progressivement pour optimiser les performances :
    </p>
    <ul>
        <li><strong>Miniatures lazy-loading</strong> : Les images se chargent au besoin</li>
        <li><strong>Pagination côté serveur</strong> : Seules les 16 vidéos de la page sont transmises</li>
        <li><strong>Cache navigateur</strong> : Les pages visitées sont mises en cache</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Accéder à une vidéo</h2>
    <p>
        Pour visionner une vidéo depuis la page d'accueil :
    </p>
    <ul>
        <li><strong>Clic sur la vignette</strong> : Ouvre la page de détail de la vidéo</li>
        <li><strong>Clic sur le titre</strong> : Même action, ouvre la page de détail</li>
        <li><strong>Navigation au clavier</strong> : Utilisez Tab pour naviguer, Entrée pour ouvrir</li>
    </ul>

    <h3>Page de détail</h3>
    <p>
        Une fois sur la page de détail, vous pouvez :
    </p>
    <ul>
        <li>Visionner la vidéo avec le lecteur intégré</li>
        <li>Consulter toutes les métadonnées</li>
        <li>Voir la liste complète des participants</li>
        <li>Accéder aux projets associés</li>
        <li>Modifier les informations (professeurs uniquement)</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Filtres et tri (à venir)</h2>
    <div class="docs-warning">
        <p>
            <strong>Fonctionnalité en développement :</strong> Dans les versions futures,
            vous pourrez filtrer les vidéos par promotion, type de projet, thématique et participants.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Cas d'usage</h2>

    <h3>Découvrir les derniers projets</h3>
    <p>
        Les vidéos sont triées par date d'ajout décroissante. Consultez simplement la première page
        pour voir les projets les plus récents.
    </p>

    <h3>Parcourir l'historique</h3>
    <p>
        Utilisez la pagination pour remonter dans le temps et explorer les projets des promotions
        précédentes.
    </p>

    <h3>Recherche ciblée</h3>
    <p>
        Si vous cherchez une vidéo spécifique, utilisez la barre de recherche plutôt que de parcourir
        toutes les pages. Consultez la section <a href="{{ route('docs.interface.search') }}">Recherche</a>
        pour plus d'informations.
    </p>
</div>

<div class="docs-navigation-buttons">
    <a href="{{ route('docs.getting-started') }}" class="docs-nav-button">
        <i class="fa-solid fa-arrow-left"></i>
        Guide de démarrage
    </a>
    <a href="{{ route('docs.interface.navbar') }}" class="docs-nav-button">
        Barre de navigation
        <i class="fa-solid fa-arrow-right"></i>
    </a>
</div>
@endsection

@extends('layouts.documentation')

@section('content')
<div class="docs-breadcrumb">
    <a href="{{ route('home') }}">Accueil</a>
    <span>/</span>
    <a href="{{ route('docs.index') }}">Documentation</a>
    <span>/</span>
    <span>Interface</span>
    <span>/</span>
    <span>Lecteur vidéo</span>
</div>

<h1 class="docs-page-title">Lecteur vidéo</h1>

<div class="docs-section">
    <p>
        BTSPlay utilise un lecteur vidéo moderne basé sur <strong>Plyr</strong> et <strong>HLS.js</strong>
        pour offrir une expérience de visionnage optimale. Le lecteur prend en charge le streaming adaptatif
        et propose de nombreux contrôles de lecture.
    </p>
</div>

<div class="docs-section">
    <h2>Accéder au lecteur</h2>
    <p>
        Pour visionner une vidéo :
    </p>
    <ul>
        <li><strong>Depuis l'accueil</strong> : Cliquez sur une vignette de la grille</li>
        <li><strong>Depuis la recherche</strong> : Cliquez sur un résultat de recherche</li>
        <li><strong>Via URL directe</strong> : Accédez directement à /medias/{id}</li>
    </ul>

    <p>
        La page de détail s'ouvre avec le lecteur vidéo occupant la zone principale de l'écran.
    </p>
</div>

<div class="docs-section">
    <h2>Interface du lecteur</h2>

    <h3>Zone de lecture</h3>
    <ul>
        <li><strong>Lecteur Plyr</strong> : Interface moderne et épurée</li>
        <li><strong>Ratio 16:9</strong> : Format standard pour les vidéos audiovisuelles</li>
        <li><strong>Responsive</strong> : S'adapte à la taille de l'écran</li>
        <li><strong>Poster</strong> : Miniature affichée avant le début de la lecture</li>
    </ul>

    <h3>Barre de contrôle</h3>
    <p>
        La barre de contrôle apparaît en bas du lecteur et propose :
    </p>
    <ul>
        <li><strong>Bouton Play/Pause</strong> : Contrôle la lecture</li>
        <li><strong>Barre de progression</strong> : Indique la position dans la vidéo</li>
        <li><strong>Temps écoulé / Durée totale</strong> : Affichage des timecodes</li>
        <li><strong>Contrôle du volume</strong> : Curseur de volume avec icône muet</li>
        <li><strong>Paramètres</strong> : Qualité vidéo et vitesse de lecture</li>
        <li><strong>Plein écran</strong> : Bascule en mode plein écran</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Contrôles de lecture</h2>

    <h3>Lecture / Pause</h3>
    <ul>
        <li><strong>Bouton Play/Pause</strong> : Clic sur l'icône en bas à gauche</li>
        <li><strong>Clic sur la vidéo</strong> : Met en pause ou reprend la lecture</li>
        <li><strong>Barre d'espace</strong> : Raccourci clavier pour Play/Pause</li>
    </ul>

    <h3>Navigation temporelle</h3>
    <ul>
        <li><strong>Barre de progression</strong> : Cliquez ou glissez pour vous déplacer dans la vidéo</li>
        <li><strong>Flèches gauche/droite</strong> : Avance ou recule de 5 secondes</li>
        <li><strong>Survol de la barre</strong> : Affiche une prévisualisation du moment (si disponible)</li>
    </ul>

    <h3>Volume</h3>
    <ul>
        <li><strong>Curseur de volume</strong> : Ajustez le niveau sonore de 0 à 100%</li>
        <li><strong>Bouton muet</strong> : Coupe ou rétablit le son instantanément</li>
        <li><strong>Touches +/-</strong> : Augmentent ou diminuent le volume par paliers</li>
        <li><strong>Touche M</strong> : Active/désactive le mode muet</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Paramètres avancés</h2>

    <h3>Qualité vidéo</h3>
    <p>
        BTSPlay utilise le streaming adaptatif HLS pour ajuster automatiquement la qualité :
    </p>
    <ul>
        <li><strong>Auto (recommandé)</strong> : La qualité s'adapte à votre connexion internet</li>
        <li><strong>Sélection manuelle</strong> : Choisissez une qualité fixe (720p, 1080p, etc.)</li>
        <li><strong>Basculement fluide</strong> : Changement de qualité sans interruption</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Conseil :</strong> Le mode "Auto" est recommandé pour une lecture optimale.
            Il ajuste dynamiquement la qualité pour éviter les interruptions de buffering.
        </p>
    </div>

    <h3>Vitesse de lecture</h3>
    <p>
        Modifiez la vitesse de lecture depuis le menu paramètres :
    </p>
    <ul>
        <li><strong>0.5x</strong> : Lecture ralentie (50%)</li>
        <li><strong>0.75x</strong> : Lecture ralentie (75%)</li>
        <li><strong>1x</strong> : Vitesse normale (par défaut)</li>
        <li><strong>1.25x</strong> : Lecture accélérée (125%)</li>
        <li><strong>1.5x</strong> : Lecture accélérée (150%)</li>
        <li><strong>2x</strong> : Lecture très rapide (200%)</li>
    </ul>

    <div class="docs-warning">
        <p>
            <strong>Note technique :</strong> La modification de la vitesse ne change pas la hauteur (pitch)
            de l'audio. Les voix restent naturelles même en lecture accélérée.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Mode plein écran</h2>

    <h3>Activer le plein écran</h3>
    <ul>
        <li><strong>Bouton plein écran</strong> : Icône en bas à droite du lecteur</li>
        <li><strong>Double-clic</strong> : Sur la zone de lecture</li>
        <li><strong>Touche F</strong> : Raccourci clavier</li>
    </ul>

    <h3>Quitter le plein écran</h3>
    <ul>
        <li><strong>Bouton de sortie</strong> : Dans les contrôles du lecteur</li>
        <li><strong>Touche Échap</strong> : Raccourci clavier universel</li>
        <li><strong>Double-clic</strong> : Sur la vidéo en mode plein écran</li>
    </ul>

    <h3>Comportement en plein écran</h3>
    <ul>
        <li><strong>Contrôles masqués</strong> : Disparaissent après 3 secondes d'inactivité</li>
        <li><strong>Mouvement de souris</strong> : Fait réapparaître les contrôles</li>
        <li><strong>Tous les raccourcis actifs</strong> : Fonctionnent normalement</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Raccourcis clavier</h2>
    <p>
        Le lecteur Plyr prend en charge de nombreux raccourcis pour un contrôle rapide :
    </p>
    <ul>
        <li><strong>Barre d'espace</strong> : Lecture / Pause</li>
        <li><strong>Flèche gauche</strong> : Reculer de 5 secondes</li>
        <li><strong>Flèche droite</strong> : Avancer de 5 secondes</li>
        <li><strong>Flèche haut</strong> : Augmenter le volume</li>
        <li><strong>Flèche bas</strong> : Diminuer le volume</li>
        <li><strong>M</strong> : Activer/désactiver le son</li>
        <li><strong>F</strong> : Basculer en plein écran</li>
        <li><strong>0-9</strong> : Sauter à 0%-90% de la vidéo</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Streaming et performance</h2>

    <h3>Technologie HLS</h3>
    <p>
        BTSPlay utilise HLS (HTTP Live Streaming) pour diffuser les vidéos :
    </p>
    <ul>
        <li><strong>Streaming adaptatif</strong> : Qualité ajustée selon la bande passante</li>
        <li><strong>Chargement progressif</strong> : La vidéo se charge par segments</li>
        <li><strong>Reprise automatique</strong> : En cas de perte de connexion temporaire</li>
        <li><strong>Buffering intelligent</strong> : Précharge les segments suivants</li>
    </ul>

    <h3>Formats supportés</h3>
    <ul>
        <li><strong>MP4</strong> : Format principal pour le streaming</li>
        <li><strong>H.264</strong> : Codec vidéo standard</li>
        <li><strong>AAC</strong> : Codec audio</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Note :</strong> Les vidéos au format MXF (archive) doivent être transcodées en MP4
            avant d'être visionnables. Cette opération est gérée automatiquement par les administrateurs.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Informations complémentaires</h2>

    <h3>Sous le lecteur</h3>
    <p>
        La page de détail affiche plusieurs sections d'information :
    </p>
    <ul>
        <li><strong>Titre et métadonnées</strong> : Description complète du projet</li>
        <li><strong>Participants</strong> : Liste des étudiants avec leurs rôles (réalisateur, cadreur, etc.)</li>
        <li><strong>Projets associés</strong> : Liens vers d'autres vidéos du même projet</li>
        <li><strong>Propriétés personnalisées</strong> : Champs spécifiques ajoutés par les professeurs</li>
    </ul>

    <h3>Actions disponibles</h3>
    <p>
        Pour les professeurs, des boutons d'action apparaissent :
    </p>
    <ul>
        <li><strong>Modifier</strong> : Éditer les métadonnées de la vidéo</li>
        <li><strong>Supprimer</strong> : Retirer la vidéo de la base (avec confirmation)</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Résolution de problèmes</h2>

    <h3>La vidéo ne se charge pas</h3>
    <ul>
        <li>Vérifiez votre connexion internet</li>
        <li>Actualisez la page (F5)</li>
        <li>Essayez avec un autre navigateur</li>
        <li>Contactez un administrateur si le problème persiste</li>
    </ul>

    <h3>Lecture saccadée</h3>
    <ul>
        <li>Laissez la vidéo buffuriser quelques secondes</li>
        <li>Réduisez manuellement la qualité dans les paramètres</li>
        <li>Fermez les autres onglets consommant de la bande passante</li>
    </ul>

    <h3>Pas de son</h3>
    <ul>
        <li>Vérifiez que le lecteur n'est pas en mode muet</li>
        <li>Vérifiez le volume système de votre ordinateur</li>
        <li>Essayez de désactiver puis réactiver le son dans le lecteur</li>
    </ul>
</div>

<div class="docs-navigation-buttons">
    <a href="{{ route('docs.interface.navbar') }}" class="docs-nav-button">
        <i class="fa-solid fa-arrow-left"></i>
        Barre de navigation
    </a>
    <a href="{{ route('docs.interface.search') }}" class="docs-nav-button">
        Recherche
        <i class="fa-solid fa-arrow-right"></i>
    </a>
</div>
@endsection

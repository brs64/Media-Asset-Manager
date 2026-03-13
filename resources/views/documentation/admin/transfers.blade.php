@extends('layouts.documentation')

@section('content')
<div class="docs-breadcrumb">
    <a href="{{ route('home') }}">Accueil</a>
    <span>/</span>
    <a href="{{ route('docs.index') }}">Documentation</a>
    <span>/</span>
    <span>Administration</span>
    <span>/</span>
    <span>Transferts</span>
</div>

<h1 class="docs-page-title">Transferts et transcodage</h1>

<div class="docs-section">
    <p>
        L'onglet "Transferts" permet de gérer le transcodage des vidéos via <strong>FFAStrans</strong>,
        un service externe dédié à la conversion de formats vidéo. C'est ici que vous transformez les
        fichiers MXF d'archive en MP4 pour le streaming web.
    </p>
</div>

<div class="docs-section">
    <h2>Qu'est-ce que le transcodage ?</h2>
    <p>
        Le transcodage est le processus de conversion d'une vidéo d'un format à un autre :
    </p>
    <ul>
        <li><strong>Format source</strong> : MXF (Material Exchange Format) - haute qualité, fichiers volumineux</li>
        <li><strong>Format cible</strong> : MP4 avec codec H.264 - optimisé pour le streaming web</li>
        <li><strong>Objectif</strong> : Rendre les vidéos lisibles dans les navigateurs web</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Pourquoi transcoder ?</strong> Les fichiers MXF sont des formats professionnels non
            supportés nativement par les navigateurs web. Le transcodage en MP4/H.264 permet une lecture
            universelle et un streaming adaptatif via HLS.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>FFAStrans : le service de transcodage</h2>
    <p>
        <strong>FFAStrans</strong> est une solution professionnelle de transcodage vidéo basée sur FFmpeg :
    </p>
    <ul>
        <li><strong>Moteur FFmpeg</strong> : Outil de référence pour la manipulation vidéo</li>
        <li><strong>Workflows</strong> : Chaînes de traitement prédéfinies</li>
        <li><strong>API REST</strong> : BTSPlay communique avec FFAStrans via HTTP</li>
        <li><strong>Exécution asynchrone</strong> : Les jobs tournent en arrière-plan</li>
    </ul>

    <h3>Architecture</h3>
    <ul>
        <li><strong>Serveur FFAStrans</strong> : Service indépendant de BTSPlay</li>
        <li><strong>Communication API</strong> : BTSPlay envoie des requêtes HTTP</li>
        <li><strong>Files d'attente</strong> : FFAStrans gère plusieurs jobs simultanément</li>
        <li><strong>Surveillance</strong> : BTSPlay interroge régulièrement le statut des jobs</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Interface de l'onglet Transferts</h2>

    <h3>Section 1 : Lancer un transcodage</h3>
    <p>
        En haut de la page, vous trouverez le formulaire pour démarrer un nouveau job :
    </p>
    <ul>
        <li><strong>Sélection du média</strong> : Liste déroulante des médias ayant un fichier MXF</li>
        <li><strong>Workflow FFAStrans</strong> : Choix du workflow de transcodage à utiliser</li>
        <li><strong>Bouton "Lancer le transcodage"</strong> : Démarre le job</li>
    </ul>

    <h3>Section 2 : Liste des transferts</h3>
    <p>
        Un tableau affiche tous les jobs de transcodage :
    </p>
    <ul>
        <li><strong>Job ID</strong> : Identifiant unique du job FFAStrans</li>
        <li><strong>Média</strong> : Nom de la vidéo en cours de transcodage</li>
        <li><strong>Workflow</strong> : Nom du workflow utilisé</li>
        <li><strong>Statut</strong> : État actuel du job (en attente, en cours, terminé, erreur)</li>
        <li><strong>Progression</strong> : Barre de progression et pourcentage</li>
        <li><strong>Date de début</strong> : Quand le job a été lancé</li>
        <li><strong>Actions</strong> : Boutons pour surveiller ou annuler</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Lancer un transcodage</h2>

    <h3>Étapes</h3>
    <ul>
        <li><strong>1. Sélectionnez un média</strong> : Choisissez une vidéo ayant un fichier MXF source</li>
        <li><strong>2. Choisissez un workflow</strong> : Sélectionnez le workflow adapté (généralement "MXF to MP4 Web")</li>
        <li><strong>3. Lancez le job</strong> : Cliquez sur "Lancer le transcodage"</li>
        <li><strong>4. Confirmation</strong> : Un message indique que le job a été soumis</li>
    </ul>

    <h3>Workflows disponibles</h3>
    <p>
        Les workflows sont configurés dans FFAStrans. Exemples courants :
    </p>
    <ul>
        <li><strong>MXF to MP4 Web</strong> : Conversion standard pour le streaming</li>
        <li><strong>MXF to MP4 HD</strong> : Haute qualité 1080p</li>
        <li><strong>MXF to MP4 4K</strong> : Très haute qualité 4K (lent)</li>
        <li><strong>Quick Transcode</strong> : Conversion rapide qualité réduite</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Conseil :</strong> Utilisez le workflow "MXF to MP4 Web" par défaut. Il offre un bon
            compromis entre qualité et taille de fichier pour le streaming web.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Surveiller les transferts</h2>

    <h3>Statuts des jobs</h3>
    <ul>
        <li><strong>Queued (En attente)</strong> : Le job est dans la file d'attente FFAStrans</li>
        <li><strong>Processing (En cours)</strong> : Le transcodage est en cours d'exécution</li>
        <li><strong>Success (Terminé)</strong> : Le transcodage s'est terminé avec succès</li>
        <li><strong>Failed (Erreur)</strong> : Le job a échoué, consultez les logs</li>
        <li><strong>Cancelled (Annulé)</strong> : Le job a été manuellement annulé</li>
    </ul>

    <h3>Progression en temps réel</h3>
    <p>
        Pour les jobs en cours :
    </p>
    <ul>
        <li><strong>Barre de progression</strong> : Affichage visuel du pourcentage d'avancement</li>
        <li><strong>Pourcentage</strong> : Valeur numérique (0-100%)</li>
        <li><strong>Temps écoulé</strong> : Durée depuis le début du job</li>
        <li><strong>Temps estimé</strong> : Temps restant approximatif (si disponible)</li>
    </ul>

    <h3>Rafraîchissement automatique</h3>
    <ul>
        <li><strong>Polling AJAX</strong> : La page interroge le serveur toutes les 5 secondes</li>
        <li><strong>Mise à jour live</strong> : Les statuts et progressions se mettent à jour automatiquement</li>
        <li><strong>Notifications</strong> : Alertes visuelles quand un job se termine</li>
    </ul>

    <div class="docs-warning">
        <p>
            <strong>Important :</strong> Gardez l'onglet ouvert pour suivre la progression en temps réel.
            Si vous fermez la page, le transcodage continue, mais vous ne verrez plus les mises à jour.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Annuler un transfert</h2>
    <p>
        Si un job prend trop de temps ou a été lancé par erreur, vous pouvez l'annuler :
    </p>
    <ul>
        <li><strong>Cliquez sur "Annuler"</strong> : Bouton rouge dans la colonne Actions</li>
        <li><strong>Confirmation</strong> : Une popup demande de confirmer l'annulation</li>
        <li><strong>Arrêt immédiat</strong> : FFAStrans stoppe le job</li>
        <li><strong>Fichiers partiels</strong> : Les fichiers partiellement transcodés sont supprimés</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Note :</strong> L'annulation d'un job est irréversible. Vous devrez relancer un
            nouveau transcodage si vous changez d'avis.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Après le transcodage</h2>

    <h3>Fichier MP4 créé</h3>
    <p>
        Une fois le transcodage terminé avec succès :
    </p>
    <ul>
        <li><strong>Fichier MP4 généré</strong> : Stocké sur le NAS PAD</li>
        <li><strong>Base de données mise à jour</strong> : Le média est marqué comme "disponible pour streaming"</li>
        <li><strong>Miniature extraite</strong> : Une image de prévisualisation est générée</li>
        <li><strong>Segments HLS</strong> : Fichiers .ts et playlist .m3u8 créés pour le streaming adaptatif</li>
    </ul>

    <h3>Synchronisation nécessaire</h3>
    <p>
        Après un transcodage réussi, n'oubliez pas de :
    </p>
    <ul>
        <li><strong>Retourner à l'onglet "Base de données"</strong></li>
        <li><strong>Lancer une synchronisation NAS PAD</strong></li>
        <li><strong>Vérifier que le MP4 est bien associé</strong> au média</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Astuce :</strong> Cette étape pourra être automatisée dans les futures versions.
            Pour l'instant, elle doit être faite manuellement.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Transcodage par lot (à venir)</h2>
    <div class="docs-warning">
        <p>
            <strong>Fonctionnalité en développement :</strong> Dans les versions futures, vous pourrez
            sélectionner plusieurs médias et lancer le transcodage en masse, avec gestion de priorités
            et planification.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Bonnes pratiques</h2>

    <h3>Planification des transcodages</h3>
    <ul>
        <li><strong>Horaires creuses</strong> : Lancez les jobs la nuit ou le week-end</li>
        <li><strong>Par petits lots</strong> : Évitez de saturer FFAStrans avec trop de jobs simultanés</li>
        <li><strong>Priorités</strong> : Transcodez d'abord les vidéos les plus récentes ou importantes</li>
    </ul>

    <h3>Surveillance</h3>
    <ul>
        <li><strong>Vérifiez les erreurs</strong> : Consultez les logs en cas d'échec</li>
        <li><strong>Testez la qualité</strong> : Visionnez les MP4 générés pour vérifier la qualité</li>
        <li><strong>Contrôlez l'espace disque</strong> : Assurez-vous que le NAS PAD a assez d'espace</li>
    </ul>

    <h3>Optimisation</h3>
    <ul>
        <li><strong>Workflow adapté</strong> : Choisissez le workflow selon vos besoins (web, HD, 4K)</li>
        <li><strong>Compression</strong> : Privilégiez les workflows compressés pour économiser de l'espace</li>
        <li><strong>Résolution</strong> : 720p ou 1080p sont suffisants pour la plupart des cas</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Dépannage</h2>

    <h3>Le job reste bloqué en "Queued"</h3>
    <ul>
        <li>Vérifiez que FFAStrans est démarré et accessible</li>
        <li>Consultez les logs FFAStrans sur le serveur</li>
        <li>Testez la connexion API depuis l'onglet "Paramètres"</li>
    </ul>

    <h3>Le job échoue systématiquement</h3>
    <ul>
        <li>Vérifiez que le fichier MXF source est accessible</li>
        <li>Assurez-vous que le workflow FFAStrans est correctement configuré</li>
        <li>Consultez les logs détaillés du job dans FFAStrans</li>
        <li>Vérifiez l'espace disque disponible sur le NAS PAD</li>
    </ul>

    <h3>Le MP4 généré ne fonctionne pas</h3>
    <ul>
        <li>Vérifiez les paramètres du workflow (codec, résolution)</li>
        <li>Testez le fichier MP4 directement sur le NAS avec VLC</li>
        <li>Relancez le transcodage avec un workflow différent</li>
    </ul>

    <h3>Progression bloquée à un pourcentage</h3>
    <ul>
        <li>Attendez quelques minutes, certaines étapes sont longues</li>
        <li>Rafraîchissez la page manuellement (F5)</li>
        <li>Consultez les logs FFAStrans pour voir l'activité réelle</li>
        <li>En dernier recours, annulez et relancez le job</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Informations techniques</h2>

    <h3>Communication BTSPlay ↔ FFAStrans</h3>
    <ul>
        <li><strong>API REST</strong> : Requêtes HTTP GET/POST</li>
        <li><strong>Endpoints</strong> : /api/workflows, /api/submit, /api/status/{jobId}</li>
        <li><strong>Format</strong> : JSON pour les échanges de données</li>
        <li><strong>Authentification</strong> : Clé API ou IP whitelisting (selon config)</li>
    </ul>

    <h3>Service Laravel</h3>
    <ul>
        <li><strong>FfastransService</strong> : Classe PHP gérant la communication</li>
        <li><strong>Méthodes</strong> : getWorkflows(), submitJob(), checkStatus(), cancelJob()</li>
        <li><strong>Configuration</strong> : Variables d'environnement dans .env</li>
    </ul>

    <h3>Streaming HLS</h3>
    <p>
        Le transcodage génère non seulement un MP4, mais aussi les segments HLS :
    </p>
    <ul>
        <li><strong>Segments .ts</strong> : Morceaux de 10 secondes de vidéo</li>
        <li><strong>Playlist .m3u8</strong> : Index des segments</li>
        <li><strong>Qualités multiples</strong> : 360p, 720p, 1080p pour le streaming adaptatif</li>
    </ul>
</div>

<div class="docs-navigation-buttons">
    <a href="{{ route('docs.admin.database') }}" class="docs-nav-button">
        <i class="fa-solid fa-arrow-left"></i>
        Base de données
    </a>
    <a href="{{ route('docs.admin.users') }}" class="docs-nav-button">
        Gestion des utilisateurs
        <i class="fa-solid fa-arrow-right"></i>
    </a>
</div>
@endsection

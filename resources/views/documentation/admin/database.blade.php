@extends('layouts.documentation')

@section('content')
<div class="docs-breadcrumb">
    <a href="{{ route('home') }}">Accueil</a>
    <span>/</span>
    <a href="{{ route('docs.index') }}">Documentation</a>
    <span>/</span>
    <span>Administration</span>
    <span>/</span>
    <span>Base de données</span>
</div>

<h1 class="docs-page-title">Base de données</h1>

<div class="docs-section">
    <p>
        L'onglet "Base de données" est le premier onglet du panneau d'administration. Il permet de
        synchroniser les médias stockés sur les serveurs NAS avec la base de données de BTSPlay,
        assurant ainsi que l'application affiche toujours les vidéos disponibles.
    </p>
</div>

<div class="docs-section">
    <h2>Vue d'ensemble</h2>
    <p>
        Cet onglet propose trois modes de synchronisation pour intégrer les vidéos dans BTSPlay :
    </p>
    <ul>
        <li><strong>Synchronisation depuis NAS ARCH (MXF)</strong> : Scanner le serveur d'archive</li>
        <li><strong>Synchronisation depuis NAS PAD (MP4)</strong> : Scanner le serveur de diffusion</li>
        <li><strong>Synchronisation depuis chemin local</strong> : Ajouter des vidéos en développement</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Rappel :</strong> BTSPlay fonctionne avec deux serveurs NAS :
        </p>
        <ul style="margin-top: 0.5rem;">
            <li><strong>NAS ARCH</strong> : Stocke les fichiers MXF (format d'archive haute qualité)</li>
            <li><strong>NAS PAD</strong> : Stocke les fichiers MP4 (format de diffusion web)</li>
        </ul>
    </div>
</div>

<div class="docs-section">
    <h2>Synchronisation depuis NAS ARCH</h2>
    <p>
        Cette fonction scanne le serveur FTP d'archive pour découvrir les nouvelles vidéos au format MXF.
    </p>

    <h3>Processus de synchronisation</h3>
    <ul>
        <li><strong>Connexion FTP</strong> : BTSPlay se connecte au serveur NAS ARCH via FTP</li>
        <li><strong>Scan récursif</strong> : Parcourt tous les dossiers à la recherche de fichiers MXF</li>
        <li><strong>Vérification</strong> : Identifie les fichiers non encore présents dans la base</li>
        <li><strong>Indexation</strong> : Crée une entrée en base pour chaque nouveau fichier</li>
        <li><strong>Métadonnées</strong> : Extrait les informations disponibles (nom, taille, date)</li>
    </ul>

    <h3>Utilisation</h3>
    <ul>
        <li>Cliquez sur le bouton <strong>"Synchroniser depuis FTP ARCH"</strong></li>
        <li>Le système lance un job en arrière-plan</li>
        <li>Une notification s'affiche indiquant le début de l'opération</li>
        <li>La page se met à jour automatiquement avec la progression</li>
        <li>Un message de succès apparaît à la fin du scan</li>
    </ul>

    <h3>Informations affichées</h3>
    <ul>
        <li><strong>Nombre de fichiers trouvés</strong> : Total de MXF sur le serveur</li>
        <li><strong>Nombre de nouveaux médias</strong> : Fichiers ajoutés à la base</li>
        <li><strong>Durée de l'opération</strong> : Temps nécessaire au scan</li>
        <li><strong>Erreurs éventuelles</strong> : Fichiers illisibles ou inaccessibles</li>
    </ul>

    <div class="docs-warning">
        <p>
            <strong>Important :</strong> Cette opération peut prendre plusieurs minutes selon le nombre
            de fichiers sur le serveur. Ne fermez pas la page pendant le traitement.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Synchronisation depuis NAS PAD</h2>
    <p>
        Cette fonction scanne le serveur FTP de diffusion pour découvrir les vidéos au format MP4
        prêtes pour le streaming.
    </p>

    <h3>Processus de synchronisation</h3>
    <ul>
        <li><strong>Connexion FTP</strong> : BTSPlay se connecte au serveur NAS PAD via FTP</li>
        <li><strong>Scan récursif</strong> : Parcourt tous les dossiers à la recherche de fichiers MP4</li>
        <li><strong>Association</strong> : Tente de lier les MP4 aux MXF correspondants dans la base</li>
        <li><strong>Indexation</strong> : Crée une entrée pour les MP4 non associés</li>
        <li><strong>Mise à jour</strong> : Marque les médias comme "disponibles pour streaming"</li>
    </ul>

    <h3>Utilisation</h3>
    <ul>
        <li>Cliquez sur le bouton <strong>"Synchroniser depuis FTP PAD"</strong></li>
        <li>Le système lance un job en arrière-plan</li>
        <li>La progression s'affiche en temps réel</li>
        <li>Les médias détectés apparaissent dans la grille d'accueil</li>
    </ul>

    <h3>Association automatique</h3>
    <p>
        BTSPlay tente d'associer automatiquement les MP4 aux MXF correspondants en comparant :
    </p>
    <ul>
        <li><strong>Noms de fichiers</strong> : Correspondance partielle des noms</li>
        <li><strong>Structure de dossiers</strong> : Arborescence identique sur les deux NAS</li>
        <li><strong>Métadonnées</strong> : Dates de création proches</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Conseil :</strong> Pour une association optimale, assurez-vous que les fichiers MXF
            et MP4 conservent des noms similaires et sont organisés dans la même structure de dossiers.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Synchronisation depuis chemin local</h2>
    <p>
        Cette fonction est principalement utilisée en environnement de développement pour ajouter des
        vidéos stockées localement sur le serveur BTSPlay.
    </p>

    <h3>Cas d'usage</h3>
    <ul>
        <li><strong>Développement local</strong> : Tester l'application avec des vidéos locales</li>
        <li><strong>Démonstration</strong> : Ajouter rapidement des vidéos sans passer par les NAS</li>
        <li><strong>Migration</strong> : Importer des vidéos depuis un autre système</li>
    </ul>

    <h3>Utilisation</h3>
    <ul>
        <li>Saisissez le <strong>chemin absolu</strong> du dossier contenant les vidéos</li>
        <li>Exemple : <code>/var/www/storage/videos/</code></li>
        <li>Cliquez sur <strong>"Synchroniser depuis chemin local"</strong></li>
        <li>Le système scanne le dossier et crée les entrées en base</li>
    </ul>

    <div class="docs-warning">
        <p>
            <strong>Attention :</strong> Cette fonction ne doit pas être utilisée en production. Les
            vidéos doivent normalement transiter par les serveurs NAS pour garantir la sauvegarde et
            la disponibilité.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Gestion des médias synchronisés</h2>

    <h3>Liste des médias</h3>
    <p>
        Sous les boutons de synchronisation, un tableau affiche les médias récemment ajoutés :
    </p>
    <ul>
        <li><strong>ID</strong> : Identifiant unique en base de données</li>
        <li><strong>Titre</strong> : Nom du fichier ou titre saisi</li>
        <li><strong>Format</strong> : MXF, MP4 ou les deux</li>
        <li><strong>Taille</strong> : Poids du fichier</li>
        <li><strong>Date d'ajout</strong> : Quand le média a été indexé</li>
        <li><strong>Statut</strong> : Disponible, en transcodage, erreur, etc.</li>
    </ul>

    <h3>Actions disponibles</h3>
    <ul>
        <li><strong>Voir</strong> : Ouvrir la page de détail du média</li>
        <li><strong>Modifier</strong> : Éditer les métadonnées (titre, description, etc.)</li>
        <li><strong>Supprimer</strong> : Retirer l'entrée de la base (ne supprime pas le fichier physique)</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Planification et automatisation</h2>

    <h3>Synchronisation manuelle vs automatique</h3>
    <ul>
        <li><strong>Manuelle</strong> : Lancée à la demande depuis cette interface</li>
        <li><strong>Automatique</strong> : Configurable via des tâches planifiées (cron)</li>
    </ul>

    <h3>Configurer une synchronisation automatique</h3>
    <p>
        Pour automatiser les scans quotidiens (fonctionnalité avancée) :
    </p>
    <ul>
        <li>Ajoutez une tâche dans le scheduler Laravel (app/Console/Kernel.php)</li>
        <li>Configurez un cron pour exécuter <code>php artisan schedule:run</code> chaque minute</li>
        <li>Planifiez l'exécution de la synchronisation aux heures creuses (ex: 3h du matin)</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>À venir :</strong> Une interface de planification sera ajoutée dans l'onglet
            "Paramètres" pour configurer les scans automatiques sans toucher au code.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Bonnes pratiques</h2>

    <h3>Fréquence de synchronisation</h3>
    <ul>
        <li><strong>Quotidienne</strong> : Recommandé pour maintenir la base à jour</li>
        <li><strong>Après ajout de vidéos</strong> : Lancez un scan immédiatement après avoir uploadé des fichiers sur les NAS</li>
        <li><strong>Avant démonstration</strong> : Assurez-vous que toutes les vidéos sont indexées</li>
    </ul>

    <h3>Ordre de synchronisation</h3>
    <ul>
        <li><strong>1. NAS ARCH d'abord</strong> : Indexez les MXF en priorité</li>
        <li><strong>2. Transcodage</strong> : Lancez les conversions MXF → MP4 (voir onglet Transferts)</li>
        <li><strong>3. NAS PAD ensuite</strong> : Synchronisez les MP4 une fois le transcodage terminé</li>
    </ul>

    <h3>Gestion des erreurs</h3>
    <ul>
        <li><strong>Connexion FTP échouée</strong> : Vérifiez les paramètres dans l'onglet "Paramètres"</li>
        <li><strong>Fichiers corrompus</strong> : Consultez les logs pour identifier les fichiers problématiques</li>
        <li><strong>Duplicatas</strong> : Le système ignore automatiquement les fichiers déjà indexés</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Dépannage</h2>

    <h3>La synchronisation ne démarre pas</h3>
    <ul>
        <li>Vérifiez que le système de queues Laravel est actif</li>
        <li>Consultez les logs dans l'onglet "Logs"</li>
        <li>Testez la connexion FTP depuis l'onglet "Paramètres"</li>
    </ul>

    <h3>Aucun nouveau média trouvé</h3>
    <ul>
        <li>Vérifiez que les fichiers sont bien présents sur le NAS</li>
        <li>Assurez-vous que les extensions sont correctes (.mxf, .mp4)</li>
        <li>Consultez la structure de dossiers sur le serveur FTP</li>
    </ul>

    <h3>Synchronisation très lente</h3>
    <ul>
        <li>Vérifiez la connexion réseau entre BTSPlay et les NAS</li>
        <li>Réduisez la profondeur de scan si les dossiers sont très imbriqués</li>
        <li>Planifiez les scans aux heures creuses</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Informations techniques</h2>

    <h3>Technologies utilisées</h3>
    <ul>
        <li><strong>League Flysystem</strong> : Abstraction du système de fichiers FTP</li>
        <li><strong>Laravel Jobs</strong> : Exécution asynchrone des scans</li>
        <li><strong>Laravel Queues</strong> : File d'attente pour les opérations longues</li>
    </ul>

    <h3>Formats supportés</h3>
    <ul>
        <li><strong>MXF</strong> : Material Exchange Format (archive)</li>
        <li><strong>MP4</strong> : MPEG-4 Part 14 (diffusion web)</li>
        <li><strong>Autres</strong> : Extensible via configuration</li>
    </ul>
</div>

<div class="docs-navigation-buttons">
    <a href="{{ route('docs.admin.overview') }}" class="docs-nav-button">
        <i class="fa-solid fa-arrow-left"></i>
        Vue d'ensemble
    </a>
    <a href="{{ route('docs.admin.transfers') }}" class="docs-nav-button">
        Transferts
        <i class="fa-solid fa-arrow-right"></i>
    </a>
</div>
@endsection

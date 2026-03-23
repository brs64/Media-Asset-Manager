@extends('layouts.documentation')

@section('content')
<div class="docs-breadcrumb">
    <a href="{{ route('home') }}">Accueil</a>
    <span>/</span>
    <a href="{{ route('docs.index') }}">Documentation</a>
    <span>/</span>
    <span>Administration</span>
    <span>/</span>
    <span>Vue d'ensemble</span>
</div>

<h1 class="docs-page-title">Administration - Vue d'ensemble</h1>

<div class="docs-section">
    <p>
        Le panneau d'administration de BTSPlay est réservé aux enseignants et offre des outils avancés
        pour gérer l'ensemble du système : synchronisation des médias, transcodage vidéo, gestion des
        utilisateurs et configuration de l'application.
    </p>
</div>

<div class="docs-warning">
    <p>
        <strong>Accès restreint :</strong> Cette section est exclusivement accessible aux comptes
        ayant le rôle "Professeur". Les étudiants ne peuvent pas accéder au panneau d'administration.
    </p>
</div>

<div class="docs-section">
    <h2>Accéder au panneau d'administration</h2>
    <p>
        Pour accéder au panneau d'administration :
    </p>
    <ul>
        <li><strong>Depuis le menu utilisateur</strong> : Cliquez sur votre nom en haut à droite, puis sur "Administration"</li>
        <li><strong>Via URL directe</strong> : Accédez à /admin depuis votre navigateur</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Note :</strong> Si vous n'êtes pas connecté avec un compte professeur, vous serez
            redirigé vers la page d'accueil ou recevrez un message d'erreur.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Structure du panneau</h2>
    <p>
        Le panneau d'administration est organisé en 6 onglets principaux, accessibles via une navigation
        horizontale en haut de la page :
    </p>

    <h3>1. Base de données</h3>
    <ul>
        <li><strong>Fonction</strong> : Synchronisation des médias depuis les serveurs NAS</li>
        <li><strong>Actions</strong> : Scan des serveurs FTP, ajout de médias, synchronisation locale</li>
        <li><strong>Utilisation</strong> : Maintien de la cohérence entre les NAS et la base de données</li>
    </ul>

    <h3>2. Transferts</h3>
    <ul>
        <li><strong>Fonction</strong> : Gestion du transcodage vidéo via FFAStrans</li>
        <li><strong>Actions</strong> : Lancer des jobs de transcodage, surveiller la progression, annuler des tâches</li>
        <li><strong>Utilisation</strong> : Conversion des vidéos MXF en MP4 pour le streaming</li>
    </ul>

    <h3>3. Réconciliation</h3>
    <ul>
        <li><strong>Fonction</strong> : Vérification de la cohérence entre les NAS</li>
        <li><strong>Actions</strong> : Comparer les fichiers, identifier les manquants, lancer la réconciliation</li>
        <li><strong>Utilisation</strong> : S'assurer que chaque vidéo existe dans les deux formats (MXF et MP4)</li>
    </ul>

    <h3>4. Paramètres</h3>
    <ul>
        <li><strong>Fonction</strong> : Configuration globale de l'application</li>
        <li><strong>Actions</strong> : Configurer les connexions FTP, FFAStrans, paramètres de backup</li>
        <li><strong>Utilisation</strong> : Personnaliser le comportement de BTSPlay</li>
    </ul>

    <h3>5. Logs</h3>
    <ul>
        <li><strong>Fonction</strong> : Consultation des journaux système</li>
        <li><strong>Actions</strong> : Visualiser les logs, télécharger les fichiers</li>
        <li><strong>Utilisation</strong> : Diagnostic et débogage en cas de problème</li>
    </ul>

    <h3>6. Utilisateurs</h3>
    <ul>
        <li><strong>Fonction</strong> : Gestion des comptes professeurs et étudiants</li>
        <li><strong>Actions</strong> : Créer, modifier, supprimer des comptes, gérer les permissions</li>
        <li><strong>Utilisation</strong> : Administration des accès à la plateforme</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Navigation dans le panneau</h2>

    <h3>Barre d'onglets</h3>
    <ul>
        <li><strong>Position</strong> : En haut de la page, sous la barre de navigation principale</li>
        <li><strong>Onglet actif</strong> : Mis en surbrillance avec une couleur différente</li>
        <li><strong>Indicateurs</strong> : Certains onglets affichent des badges de statut (tâches en cours, erreurs, etc.)</li>
    </ul>

    <h3>Retour à l'application</h3>
    <p>
        Pour quitter le panneau d'administration :
    </p>
    <ul>
        <li><strong>Logo BTSPlay</strong> : Cliquez sur le logo pour revenir à la page d'accueil</li>
        <li><strong>Barre de navigation</strong> : Utilisez les liens de la barre principale</li>
        <li><strong>Bouton retour navigateur</strong> : Fonctionne normalement</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Architecture technique</h2>
    <p>
        BTSPlay repose sur une architecture distribuée avec plusieurs composants :
    </p>

    <h3>Composants principaux</h3>
    <ul>
        <li><strong>Application Laravel</strong> : Backend et interface web</li>
        <li><strong>Base de données MySQL</strong> : Stockage des métadonnées</li>
        <li><strong>NAS ARCH</strong> : Serveur de stockage des fichiers MXF (archive)</li>
        <li><strong>NAS PAD</strong> : Serveur de stockage des fichiers MP4 (diffusion)</li>
        <li><strong>FFAStrans</strong> : Service externe de transcodage vidéo</li>
    </ul>

    <h3>Flux de données</h3>
    <ul>
        <li><strong>Ingestion</strong> : Les vidéos arrivent sur NAS ARCH au format MXF</li>
        <li><strong>Synchronisation</strong> : BTSPlay indexe les nouveaux fichiers via FTP</li>
        <li><strong>Transcodage</strong> : Les MXF sont convertis en MP4 pour le streaming</li>
        <li><strong>Stockage</strong> : Les MP4 sont placés sur NAS PAD</li>
        <li><strong>Diffusion</strong> : BTSPlay streame les MP4 via HLS</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Permissions et rôles</h2>
    <p>
        BTSPlay utilise le package <strong>Spatie Laravel Permission</strong> pour gérer les droits d'accès :
    </p>

    <h3>Rôles système</h3>
    <ul>
        <li><strong>professeur</strong> : Accès complet à l'administration</li>
        <li><strong>eleve</strong> : Accès limité à la consultation des vidéos</li>
    </ul>

    <h3>Permissions disponibles</h3>
    <ul>
        <li><strong>administrer site</strong> : Accès au panneau d'administration</li>
        <li><strong>modifier video</strong> : Édition des métadonnées</li>
        <li><strong>supprimer video</strong> : Suppression de médias</li>
        <li><strong>diffuser video</strong> : Accès au streaming</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Note :</strong> Les permissions peuvent être attribuées de manière granulaire
            depuis l'onglet "Utilisateurs" du panneau d'administration.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Bonnes pratiques</h2>

    <h3>Sécurité</h3>
    <ul>
        <li><strong>Comptes personnels</strong> : Chaque enseignant doit avoir son propre compte</li>
        <li><strong>Mots de passe forts</strong> : Utilisez des mots de passe complexes</li>
        <li><strong>Déconnexion</strong> : Fermez toujours votre session après administration</li>
        <li><strong>Logs</strong> : Consultez régulièrement les logs pour détecter les anomalies</li>
    </ul>

    <h3>Maintenance</h3>
    <ul>
        <li><strong>Synchronisation régulière</strong> : Lancez des scans quotidiens pour maintenir la base à jour</li>
        <li><strong>Surveillance des transferts</strong> : Vérifiez que les transcodages se terminent correctement</li>
        <li><strong>Réconciliation périodique</strong> : Exécutez la réconciliation hebdomadaire pour détecter les incohérences</li>
        <li><strong>Backups</strong> : Configurez des sauvegardes automatiques de la base de données</li>
    </ul>

    <h3>Performance</h3>
    <ul>
        <li><strong>Horaires creuses</strong> : Lancez les opérations lourdes hors des heures d'affluence</li>
        <li><strong>Surveillance ressources</strong> : Vérifiez l'espace disque des NAS régulièrement</li>
        <li><strong>Optimisation base</strong> : Nettoyez les entrées obsolètes périodiquement</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Support et dépannage</h2>

    <h3>Problèmes courants</h3>
    <ul>
        <li><strong>Connexion FTP échouée</strong> : Vérifiez les paramètres dans l'onglet "Paramètres"</li>
        <li><strong>Transcodage bloqué</strong> : Consultez les logs FFAStrans dans l'onglet "Transferts"</li>
        <li><strong>Vidéos manquantes</strong> : Lancez une réconciliation complète</li>
        <li><strong>Erreurs 500</strong> : Consultez les logs système dans l'onglet "Logs"</li>
    </ul>

    <h3>Ressources</h3>
    <ul>
        <li><strong>Documentation technique</strong> : Consultez le README.md du projet</li>
        <li><strong>Logs Laravel</strong> : Fichiers dans storage/logs/</li>
        <li><strong>Support technique</strong> : Contactez l'équipe de développement</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Prochaines sections</h2>
    <p>
        Explorez les guides détaillés pour chaque onglet du panneau d'administration :
    </p>
    <ul>
        <li><strong>Base de données</strong> : Synchronisation et indexation des médias</li>
        <li><strong>Transferts</strong> : Gestion du transcodage vidéo</li>
        <li><strong>Réconciliation</strong> : Vérification de la cohérence</li>
        <li><strong>Paramètres</strong> : Configuration système</li>
        <li><strong>Utilisateurs</strong> : Gestion des comptes et permissions</li>
    </ul>
</div>

<div class="docs-navigation-buttons">
    <a href="{{ route('docs.interface.video-player') }}" class="docs-nav-button">
        <i class="fa-solid fa-arrow-left"></i>
        Interface - Lecteur vidéo
    </a>
    <a href="{{ route('docs.admin.database') }}" class="docs-nav-button">
        Base de données
        <i class="fa-solid fa-arrow-right"></i>
    </a>
</div>
@endsection

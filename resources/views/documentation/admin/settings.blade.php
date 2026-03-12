@extends('layouts.documentation')

@section('content')
<div class="docs-breadcrumb">
    <a href="{{ route('home') }}">Accueil</a>
    <span>/</span>
    <a href="{{ route('docs.index') }}">Documentation</a>
    <span>/</span>
    <span>Administration</span>
    <span>/</span>
    <span>Paramètres</span>
</div>

<h1 class="docs-page-title">Paramètres</h1>

<div class="docs-section">
    <p>
        L'onglet "Paramètres" permet de configurer tous les aspects techniques de BTSPlay : connexions
        aux serveurs NAS, intégration FFAStrans, sauvegardes automatiques, et autres paramètres système.
    </p>
</div>

<div class="docs-warning">
    <p>
        <strong>Attention :</strong> Cet onglet contient des paramètres critiques. Des modifications
        incorrectes peuvent rendre l'application non fonctionnelle. Procédez avec prudence et testez
        chaque modification.
    </p>
</div>

<div class="docs-section">
    <h2>Structure de l'onglet</h2>
    <p>
        L'interface est organisée en plusieurs sections :
    </p>
    <ul>
        <li><strong>Connexions FTP</strong> : Configuration des NAS ARCH et PAD</li>
        <li><strong>FFAStrans</strong> : Paramètres du service de transcodage</li>
        <li><strong>Sauvegardes</strong> : Configuration des backups automatiques</li>
        <li><strong>Stockage</strong> : Chemins locaux et options de cache</li>
        <li><strong>Logs</strong> : Niveau de verbosité et rotation</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Connexions FTP</h2>

    <h3>NAS ARCH (Archive MXF)</h3>
    <ul>
        <li><strong>Hôte</strong> : Adresse IP ou nom de domaine du serveur</li>
        <li><strong>Port</strong> : Port FTP (généralement 21)</li>
        <li><strong>Utilisateur</strong> : Nom d'utilisateur FTP</li>
        <li><strong>Mot de passe</strong> : Mot de passe FTP (chiffré en base)</li>
        <li><strong>Chemin racine</strong> : Dossier de base sur le serveur (ex: /videos/mxf/)</li>
        <li><strong>Mode passif</strong> : Activé par défaut pour la plupart des serveurs</li>
    </ul>

    <h3>NAS PAD (Diffusion MP4)</h3>
    <ul>
        <li><strong>Hôte</strong> : Adresse IP ou nom de domaine du serveur</li>
        <li><strong>Port</strong> : Port FTP (généralement 21)</li>
        <li><strong>Utilisateur</strong> : Nom d'utilisateur FTP</li>
        <li><strong>Mot de passe</strong> : Mot de passe FTP (chiffré en base)</li>
        <li><strong>Chemin racine</strong> : Dossier de base sur le serveur (ex: /videos/mp4/)</li>
        <li><strong>Mode passif</strong> : Activé par défaut</li>
    </ul>

    <h3>Test de connexion</h3>
    <p>
        Après avoir modifié les paramètres FTP :
    </p>
    <ul>
        <li><strong>Cliquez sur "Tester la connexion"</strong> pour chaque NAS</li>
        <li><strong>Message de succès</strong> : Connexion établie, serveur accessible</li>
        <li><strong>Message d'erreur</strong> : Vérifiez les paramètres (hôte, port, identifiants)</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Conseil :</strong> Utilisez des comptes FTP dédiés avec des permissions en lecture
            seule pour BTSPlay. Cela évite les suppressions accidentelles sur les NAS.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>FFAStrans</h2>

    <h3>Configuration API</h3>
    <ul>
        <li><strong>URL de base</strong> : Adresse du serveur FFAStrans (ex: http://ffastrans.local:3000)</li>
        <li><strong>Clé API</strong> : Token d'authentification (si activé)</li>
        <li><strong>Timeout</strong> : Délai maximum d'attente pour les requêtes (en secondes)</li>
        <li><strong>Workflows par défaut</strong> : ID du workflow de transcodage standard</li>
    </ul>

    <h3>Paramètres de transcodage</h3>
    <ul>
        <li><strong>Qualité MP4</strong> : Bitrate vidéo (ex: 5000 kbps pour 1080p)</li>
        <li><strong>Résolution cible</strong> : 720p, 1080p, ou auto</li>
        <li><strong>Codec audio</strong> : AAC par défaut</li>
        <li><strong>Génération HLS</strong> : Activer/désactiver la création des segments</li>
    </ul>

    <h3>Test de connexion FFAStrans</h3>
    <ul>
        <li><strong>Cliquez sur "Tester FFAStrans"</strong></li>
        <li><strong>Récupération des workflows</strong> : Liste les workflows disponibles</li>
        <li><strong>Message de succès</strong> : API accessible, workflows détectés</li>
        <li><strong>Message d'erreur</strong> : Vérifiez l'URL et la connectivité réseau</li>
    </ul>

    <div class="docs-warning">
        <p>
            <strong>Important :</strong> Assurez-vous que BTSPlay peut joindre le serveur FFAStrans
            sur le réseau. Dans un environnement Docker, vérifiez que les conteneurs sont sur le
            même réseau ou que le port est exposé.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Sauvegardes</h2>

    <h3>Configuration</h3>
    <ul>
        <li><strong>Activer les backups</strong> : Cochez pour activer les sauvegardes automatiques</li>
        <li><strong>Fréquence</strong> : Quotidienne, hebdomadaire, ou personnalisée (cron)</li>
        <li><strong>Heure d'exécution</strong> : Moment de la journée (ex: 03:00)</li>
        <li><strong>Destination</strong> : Chemin local ou distant pour les backups</li>
        <li><strong>Rétention</strong> : Nombre de backups à conserver (ex: 7 jours)</li>
    </ul>

    <h3>Contenu sauvegardé</h3>
    <ul>
        <li><strong>Base de données MySQL</strong> : Dump SQL complet</li>
        <li><strong>Fichiers de configuration</strong> : .env et configs importantes</li>
        <li><strong>Logs système</strong> : Archives des logs récents</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Note :</strong> Les fichiers vidéo (MXF et MP4) ne sont pas inclus dans les backups
            car ils sont stockés sur les NAS. Seules les métadonnées en base sont sauvegardées.
        </p>
    </div>

    <h3>Restauration</h3>
    <p>
        Pour restaurer une sauvegarde :
    </p>
    <ul>
        <li><strong>Téléchargez le backup</strong> depuis l'onglet "Logs"</li>
        <li><strong>Extrayez l'archive</strong> (.zip ou .tar.gz)</li>
        <li><strong>Restaurez la base</strong> : <code>mysql -u user -p database < backup.sql</code></li>
        <li><strong>Copiez les configs</strong> si nécessaire</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Stockage</h2>

    <h3>Chemins locaux</h3>
    <ul>
        <li><strong>Dossier temporaire</strong> : Pour les fichiers en cours de traitement</li>
        <li><strong>Cache</strong> : Stockage des miniatures et segments HLS</li>
        <li><strong>Logs</strong> : Emplacement des fichiers de logs</li>
        <li><strong>Uploads</strong> : Dossier pour les imports utilisateur</li>
    </ul>

    <h3>Options de cache</h3>
    <ul>
        <li><strong>Activer le cache</strong> : Améliore les performances</li>
        <li><strong>Durée de vie</strong> : Temps avant expiration du cache (en heures)</li>
        <li><strong>Taille maximale</strong> : Limite d'espace disque pour le cache (en Go)</li>
        <li><strong>Nettoyage auto</strong> : Suppression automatique des anciens fichiers</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Optimisation :</strong> Activez toujours le cache pour améliorer la réactivité
            de l'application. Ajustez la taille selon l'espace disque disponible.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Logs et débogage</h2>

    <h3>Niveau de logs</h3>
    <ul>
        <li><strong>Debug</strong> : Tous les détails (développement uniquement)</li>
        <li><strong>Info</strong> : Événements importants (recommandé en production)</li>
        <li><strong>Warning</strong> : Alertes et problèmes non critiques</li>
        <li><strong>Error</strong> : Erreurs nécessitant une attention</li>
        <li><strong>Critical</strong> : Problèmes graves uniquement</li>
    </ul>

    <h3>Rotation des logs</h3>
    <ul>
        <li><strong>Quotidienne</strong> : Nouveau fichier chaque jour</li>
        <li><strong>Par taille</strong> : Nouveau fichier tous les X Mo</li>
        <li><strong>Rétention</strong> : Nombre de jours à conserver</li>
    </ul>

    <h3>Mode debug</h3>
    <ul>
        <li><strong>APP_DEBUG=true</strong> : Affiche les erreurs détaillées</li>
        <li><strong>Utilisation</strong> : Développement et diagnostic uniquement</li>
        <li><strong>JAMAIS en production</strong> : Risque de sécurité</li>
    </ul>

    <div class="docs-warning">
        <p>
            <strong>Sécurité :</strong> Ne laissez JAMAIS APP_DEBUG=true en production. Les messages
            d'erreur détaillés peuvent révéler des informations sensibles (chemins, mots de passe, etc.).
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Paramètres avancés</h2>

    <h3>Base de données</h3>
    <ul>
        <li><strong>Pool de connexions</strong> : Nombre de connexions simultanées</li>
        <li><strong>Timeout</strong> : Durée maximum des requêtes longues</li>
        <li><strong>Charset</strong> : utf8mb4 pour support emoji et caractères spéciaux</li>
    </ul>

    <h3>Performance</h3>
    <ul>
        <li><strong>Queue workers</strong> : Nombre de processus pour les jobs</li>
        <li><strong>Max execution time</strong> : Limite PHP pour les scripts longs</li>
        <li><strong>Memory limit</strong> : RAM allouée à PHP</li>
    </ul>

    <h3>Sécurité</h3>
    <ul>
        <li><strong>CSRF protection</strong> : Toujours activé</li>
        <li><strong>Session lifetime</strong> : Durée de validité des sessions (en minutes)</li>
        <li><strong>Password requirements</strong> : Règles pour les mots de passe</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Enregistrer les modifications</h2>
    <p>
        Après avoir modifié les paramètres :
    </p>
    <ul>
        <li><strong>Cliquez sur "Enregistrer"</strong> en bas de page</li>
        <li><strong>Confirmation</strong> : Message de succès affiché</li>
        <li><strong>Application immédiate</strong> : La plupart des paramètres sont actifs sans redémarrage</li>
        <li><strong>Redémarrage requis</strong> : Certains paramètres (base de données, queues) nécessitent un redémarrage</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Conseil :</strong> Testez les connexions FTP et FFAStrans après chaque modification
            pour vous assurer que tout fonctionne correctement.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Bonnes pratiques</h2>

    <h3>Configuration initiale</h3>
    <ul>
        <li><strong>Documentez vos paramètres</strong> : Notez les valeurs importantes</li>
        <li><strong>Testez chaque service</strong> : FTP, FFAStrans, base de données</li>
        <li><strong>Sauvegardez le .env</strong> : Copie sécurisée du fichier de configuration</li>
    </ul>

    <h3>Maintenance</h3>
    <ul>
        <li><strong>Revue trimestrielle</strong> : Vérifiez que les paramètres sont toujours pertinents</li>
        <li><strong>Mises à jour de mots de passe</strong> : Changez régulièrement les credentials FTP</li>
        <li><strong>Surveillance des logs</strong> : Ajustez le niveau selon les besoins</li>
    </ul>

    <h3>Sécurité</h3>
    <ul>
        <li><strong>Credentials sécurisés</strong> : Ne partagez jamais les mots de passe</li>
        <li><strong>Accès limité</strong> : Seuls les admin seniors doivent modifier ces paramètres</li>
        <li><strong>Audit trail</strong> : Consultez les logs pour voir qui a modifié quoi</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Dépannage</h2>

    <h3>Connexion FTP échoue</h3>
    <ul>
        <li>Vérifiez l'adresse IP et le port</li>
        <li>Testez avec un client FTP tiers (FileZilla)</li>
        <li>Assurez-vous que le pare-feu autorise la connexion</li>
        <li>Vérifiez les credentials</li>
    </ul>

    <h3>FFAStrans inaccessible</h3>
    <ul>
        <li>Vérifiez que le service FFAStrans est démarré</li>
        <li>Testez l'URL dans un navigateur</li>
        <li>Vérifiez la configuration réseau (Docker, firewall)</li>
        <li>Consultez les logs FFAStrans</li>
    </ul>

    <h3>Modifications non prises en compte</h3>
    <ul>
        <li>Videz le cache Laravel : <code>php artisan cache:clear</code></li>
        <li>Redémarrez les workers : <code>php artisan queue:restart</code></li>
        <li>Rechargez la configuration : <code>php artisan config:cache</code></li>
    </ul>
</div>

<div class="docs-navigation-buttons">
    <a href="{{ route('docs.admin.reconciliation') }}" class="docs-nav-button">
        <i class="fa-solid fa-arrow-left"></i>
        Réconciliation
    </a>
    <a href="{{ route('docs.admin.users') }}" class="docs-nav-button">
        Gestion des utilisateurs
        <i class="fa-solid fa-arrow-right"></i>
    </a>
</div>
@endsection

@extends('layouts.documentation')

@section('content')
<div class="docs-breadcrumb">
    <a href="{{ route('home') }}">Accueil</a>
    <span>/</span>
    <a href="{{ route('docs.index') }}">Documentation</a>
    <span>/</span>
    <span>Administration</span>
    <span>/</span>
    <span>Gestion des utilisateurs</span>
</div>

<h1 class="docs-page-title">Gestion des utilisateurs</h1>

<div class="docs-section">
    <p>
        L'onglet "Utilisateurs" permet de gérer tous les comptes de la plateforme BTSPlay : création de
        comptes professeurs et étudiants, gestion des permissions, import en masse, et administration
        des rôles.
    </p>
</div>

<div class="docs-section">
    <h2>Types d'utilisateurs</h2>
    <p>
        BTSPlay distingue deux types principaux d'utilisateurs :
    </p>

    <h3>Professeurs</h3>
    <ul>
        <li><strong>Rôle</strong> : professeur</li>
        <li><strong>Accès</strong> : Consultation des vidéos + Panneau d'administration complet</li>
        <li><strong>Permissions</strong> : Toutes les permissions par défaut</li>
        <li><strong>Cas d'usage</strong> : Enseignants, administrateurs techniques, coordinateurs</li>
    </ul>

    <h3>Étudiants</h3>
    <ul>
        <li><strong>Rôle</strong> : eleve</li>
        <li><strong>Accès</strong> : Consultation des vidéos uniquement</li>
        <li><strong>Permissions</strong> : Diffuser vidéo (lecture)</li>
        <li><strong>Cas d'usage</strong> : Étudiants du BTS Audiovisuel</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Note :</strong> Les permissions peuvent être personnalisées individuellement pour
            chaque utilisateur, quelle que soit leur catégorie de base.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Structure de l'onglet</h2>
    <p>
        L'interface est divisée en plusieurs sections :
    </p>

    <h3>Section 1 : Création de professeurs</h3>
    <ul>
        <li><strong>Formulaire de création</strong> : Nom, email, mot de passe</li>
        <li><strong>Bouton "Créer un professeur"</strong> : Soumet le formulaire</li>
        <li><strong>Liste des professeurs</strong> : Tableau des comptes professeurs existants</li>
    </ul>

    <h3>Section 2 : Création d'étudiants</h3>
    <ul>
        <li><strong>Formulaire individuel</strong> : Création manuelle d'un étudiant</li>
        <li><strong>Import CSV</strong> : Création en masse depuis un fichier</li>
        <li><strong>Liste des étudiants</strong> : Tableau des comptes étudiants existants</li>
    </ul>

    <h3>Section 3 : Gestion des permissions</h3>
    <ul>
        <li><strong>Matrice utilisateur-permission</strong> : Tableau de checkboxes</li>
        <li><strong>Modification en temps réel</strong> : Changements appliqués immédiatement</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Créer un professeur</h2>

    <h3>Formulaire de création</h3>
    <ul>
        <li><strong>Nom</strong> : Nom complet de l'enseignant</li>
        <li><strong>Email</strong> : Adresse email unique (sert d'identifiant)</li>
        <li><strong>Mot de passe</strong> : Mot de passe initial (peut être changé après)</li>
    </ul>

    <h3>Étapes</h3>
    <ul>
        <li><strong>1. Remplissez le formulaire</strong> : Saisissez les informations</li>
        <li><strong>2. Cliquez sur "Créer"</strong> : Soumet la création</li>
        <li><strong>3. Confirmation</strong> : Message de succès affiché</li>
        <li><strong>4. Email de bienvenue</strong> : Optionnel, peut être envoyé automatiquement</li>
    </ul>

    <h3>Permissions par défaut</h3>
    <p>
        Un nouveau compte professeur reçoit automatiquement :
    </p>
    <ul>
        <li><strong>administrer site</strong> : Accès au panneau admin</li>
        <li><strong>modifier video</strong> : Édition des métadonnées</li>
        <li><strong>supprimer video</strong> : Suppression de médias</li>
        <li><strong>diffuser video</strong> : Lecture des vidéos</li>
    </ul>

    <div class="docs-warning">
        <p>
            <strong>Sécurité :</strong> Communiquez le mot de passe initial de manière sécurisée
            (en personne, email chiffré, etc.). Encouragez le changement du mot de passe lors de
            la première connexion.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Créer un étudiant</h2>

    <h3>Création manuelle</h3>
    <p>
        Pour créer un seul étudiant :
    </p>
    <ul>
        <li><strong>Nom</strong> : Nom complet de l'étudiant</li>
        <li><strong>Email</strong> : Adresse email unique</li>
        <li><strong>Promotion</strong> : Année de formation (ex: "2024-2025")</li>
        <li><strong>Mot de passe</strong> : Mot de passe initial</li>
    </ul>

    <h3>Création par import CSV</h3>
    <p>
        Pour créer plusieurs étudiants en une seule opération :
    </p>
    <ul>
        <li><strong>Préparez un fichier CSV</strong> : Format : nom,email,promotion,mot_de_passe</li>
        <li><strong>Exemple de ligne</strong> : Jean Dupont,jean.dupont@example.com,2024-2025,MotDePasse123</li>
        <li><strong>Cliquez sur "Parcourir"</strong> : Sélectionnez votre fichier CSV</li>
        <li><strong>Cliquez sur "Importer"</strong> : Lance l'import en masse</li>
        <li><strong>Résultat</strong> : Un rapport indique les comptes créés et les erreurs éventuelles</li>
    </ul>

    <h3>Format du fichier CSV</h3>
    <p>
        Le fichier CSV doit respecter ce format :
    </p>
    <ul>
        <li><strong>Encodage</strong> : UTF-8 (pour les caractères accentués)</li>
        <li><strong>Séparateur</strong> : Virgule (,)</li>
        <li><strong>Première ligne</strong> : En-têtes (nom,email,promotion,mot_de_passe)</li>
        <li><strong>Lignes suivantes</strong> : Une ligne par étudiant</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Astuce :</strong> Vous pouvez exporter une liste depuis un tableur (Excel, LibreOffice)
            au format CSV. Assurez-vous de bien choisir UTF-8 comme encodage à l'export.
        </p>
    </div>

    <h3>Permissions par défaut</h3>
    <p>
        Un nouveau compte étudiant reçoit uniquement :
    </p>
    <ul>
        <li><strong>diffuser video</strong> : Permission de lire les vidéos</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Liste des utilisateurs</h2>

    <h3>Tableau des professeurs</h3>
    <p>
        Colonnes affichées :
    </p>
    <ul>
        <li><strong>ID</strong> : Identifiant unique en base</li>
        <li><strong>Nom</strong> : Nom complet</li>
        <li><strong>Email</strong> : Adresse email</li>
        <li><strong>Date de création</strong> : Quand le compte a été créé</li>
        <li><strong>Actions</strong> : Modifier, Supprimer</li>
    </ul>

    <h3>Tableau des étudiants</h3>
    <p>
        Colonnes affichées :
    </p>
    <ul>
        <li><strong>ID</strong> : Identifiant unique en base</li>
        <li><strong>Nom</strong> : Nom complet</li>
        <li><strong>Email</strong> : Adresse email</li>
        <li><strong>Promotion</strong> : Année de formation</li>
        <li><strong>Date de création</strong> : Quand le compte a été créé</li>
        <li><strong>Actions</strong> : Modifier, Supprimer</li>
    </ul>

    <h3>Actions disponibles</h3>
    <ul>
        <li><strong>Modifier</strong> : Éditer le nom, email ou promotion</li>
        <li><strong>Réinitialiser le mot de passe</strong> : Générer un nouveau mot de passe</li>
        <li><strong>Supprimer</strong> : Retirer le compte (avec confirmation)</li>
    </ul>

    <div class="docs-warning">
        <p>
            <strong>Attention :</strong> La suppression d'un compte est irréversible. L'utilisateur
            perdra immédiatement l'accès à BTSPlay. Assurez-vous de bien vouloir supprimer avant
            de confirmer.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Gestion des permissions</h2>

    <h3>Système de permissions (Spatie)</h3>
    <p>
        BTSPlay utilise le package <strong>Spatie Laravel Permission</strong> pour gérer les droits :
    </p>
    <ul>
        <li><strong>Permissions granulaires</strong> : Chaque action a sa propre permission</li>
        <li><strong>Attribution individuelle</strong> : Personnalisable par utilisateur</li>
        <li><strong>Rôles</strong> : Groupes de permissions (professeur, eleve)</li>
    </ul>

    <h3>Permissions disponibles</h3>

    <h3>1. administrer site</h3>
    <ul>
        <li><strong>Description</strong> : Accès au panneau d'administration</li>
        <li><strong>Par défaut</strong> : Professeurs uniquement</li>
        <li><strong>Portée</strong> : Tous les onglets admin (Database, Transferts, etc.)</li>
    </ul>

    <h3>2. modifier video</h3>
    <ul>
        <li><strong>Description</strong> : Éditer les métadonnées des vidéos</li>
        <li><strong>Par défaut</strong> : Professeurs uniquement</li>
        <li><strong>Portée</strong> : Titre, description, participants, propriétés custom</li>
    </ul>

    <h3>3. supprimer video</h3>
    <ul>
        <li><strong>Description</strong> : Supprimer des médias de la base</li>
        <li><strong>Par défaut</strong> : Professeurs uniquement</li>
        <li><strong>Portée</strong> : Suppression logique (fichiers conservés sur NAS)</li>
    </ul>

    <h3>4. diffuser video</h3>
    <ul>
        <li><strong>Description</strong> : Consulter et visionner les vidéos</li>
        <li><strong>Par défaut</strong> : Tous les utilisateurs</li>
        <li><strong>Portée</strong> : Streaming, téléchargement (si activé)</li>
    </ul>

    <h3>Matrice de permissions</h3>
    <p>
        L'interface affiche un tableau avec :
    </p>
    <ul>
        <li><strong>Lignes</strong> : Utilisateurs (professeurs et étudiants)</li>
        <li><strong>Colonnes</strong> : Permissions disponibles</li>
        <li><strong>Checkboxes</strong> : Cochez pour attribuer, décochez pour retirer</li>
        <li><strong>Mise à jour AJAX</strong> : Changements appliqués immédiatement sans rechargement</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Conseil :</strong> Soyez prudent avec la permission "administrer site". Accordez-la
            uniquement aux enseignants de confiance qui ont besoin d'accéder au panneau d'administration.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Gestion des rôles de production</h2>

    <h3>Rôles sur les projets</h3>
    <p>
        En plus des permissions système, BTSPlay gère les rôles de production des étudiants :
    </p>
    <ul>
        <li><strong>Réalisateur</strong> : Responsable de la direction artistique</li>
        <li><strong>Cadreur</strong> : Opérateur caméra</li>
        <li><strong>Ingénieur du son</strong> : Responsable de la prise de son</li>
        <li><strong>Monteur</strong> : Responsable du montage</li>
        <li><strong>Étalonneur</strong> : Responsable de l'étalonnage couleur</li>
        <li><strong>Scripte</strong> : Continuité</li>
        <li><strong>Assistant réalisateur</strong> : Aide à la réalisation</li>
    </ul>

    <h3>Attribution des rôles</h3>
    <p>
        Les rôles sont attribués au niveau de chaque vidéo :
    </p>
    <ul>
        <li><strong>Page de détail vidéo</strong> : Section "Participants"</li>
        <li><strong>Formulaire de métadonnées</strong> : Ajout/modification des participants</li>
        <li><strong>Plusieurs rôles possibles</strong> : Un étudiant peut avoir plusieurs rôles sur un même projet</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Note :</strong> Ces rôles sont purement informatifs et n'affectent pas les permissions
            d'accès. Ils servent à documenter les contributions de chaque étudiant aux projets.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Bonnes pratiques</h2>

    <h3>Création de comptes</h3>
    <ul>
        <li><strong>Comptes nominatifs</strong> : Créez un compte par personne, pas de comptes partagés</li>
        <li><strong>Emails institutionnels</strong> : Préférez les adresses email de l'établissement</li>
        <li><strong>Promotions cohérentes</strong> : Utilisez le même format pour toutes les promotions (ex: "2024-2025")</li>
        <li><strong>Import CSV en début d'année</strong> : Créez tous les étudiants d'un coup</li>
    </ul>

    <h3>Gestion des permissions</h3>
    <ul>
        <li><strong>Principe du moindre privilège</strong> : N'accordez que les permissions nécessaires</li>
        <li><strong>Revue régulière</strong> : Vérifiez périodiquement les permissions attribuées</li>
        <li><strong>Documentation</strong> : Notez pourquoi certains utilisateurs ont des permissions spéciales</li>
    </ul>

    <h3>Sécurité</h3>
    <ul>
        <li><strong>Mots de passe forts</strong> : Imposez des mots de passe complexes</li>
        <li><strong>Changement obligatoire</strong> : Encouragez le changement du mot de passe initial</li>
        <li><strong>Comptes inactifs</strong> : Désactivez ou supprimez les comptes des anciens étudiants</li>
        <li><strong>Logs d'accès</strong> : Surveillez les connexions suspectes</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Maintenance annuelle</h2>

    <h3>En début d'année scolaire</h3>
    <ul>
        <li><strong>Import des nouveaux étudiants</strong> : Via CSV avec la nouvelle promotion</li>
        <li><strong>Vérification des professeurs</strong> : Ajoutez les nouveaux enseignants</li>
        <li><strong>Tests de connexion</strong> : Assurez-vous que tous les comptes fonctionnent</li>
    </ul>

    <h3>En fin d'année scolaire</h3>
    <ul>
        <li><strong>Comptes sortants</strong> : Désactivez ou supprimez les comptes des diplômés</li>
        <li><strong>Archivage</strong> : Exportez la liste des utilisateurs pour archivage</li>
        <li><strong>Nettoyage</strong> : Retirez les comptes de test ou obsolètes</li>
    </ul>

    <h3>Tout au long de l'année</h3>
    <ul>
        <li><strong>Ajouts ponctuels</strong> : Créez les comptes des nouveaux arrivants</li>
        <li><strong>Réinitialisations</strong> : Aidez les utilisateurs ayant oublié leur mot de passe</li>
        <li><strong>Corrections</strong> : Modifiez les informations erronées (nom, email, promotion)</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Dépannage</h2>

    <h3>Impossible de créer un compte</h3>
    <ul>
        <li>Vérifiez que l'email n'est pas déjà utilisé</li>
        <li>Assurez-vous que tous les champs obligatoires sont remplis</li>
        <li>Consultez les logs pour voir le message d'erreur exact</li>
    </ul>

    <h3>Import CSV échoue</h3>
    <ul>
        <li>Vérifiez l'encodage du fichier (doit être UTF-8)</li>
        <li>Assurez-vous que le séparateur est bien une virgule</li>
        <li>Vérifiez qu'il n'y a pas de lignes vides ou mal formatées</li>
        <li>Testez avec un fichier contenant seulement 2-3 lignes</li>
    </ul>

    <h3>Permissions ne s'appliquent pas</h3>
    <ul>
        <li>Demandez à l'utilisateur de se déconnecter et reconnecter</li>
        <li>Vérifiez dans la base que la permission est bien attribuée</li>
        <li>Consultez les logs d'authentification</li>
    </ul>
</div>

<div class="docs-navigation-buttons">
    <a href="{{ route('docs.admin.transfers') }}" class="docs-nav-button">
        <i class="fa-solid fa-arrow-left"></i>
        Transferts
    </a>
    <a href="{{ route('docs.index') }}" class="docs-nav-button">
        Retour à l'accueil
        <i class="fa-solid fa-arrow-right"></i>
    </a>
</div>
@endsection

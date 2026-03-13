@extends('layouts.documentation')

@section('content')
<div class="docs-breadcrumb">
    <a href="{{ route('home') }}">Accueil</a>
    <span>/</span>
    <a href="{{ route('docs.index') }}">Documentation</a>
    <span>/</span>
    <span>Administration</span>
    <span>/</span>
    <span>Réconciliation</span>
</div>

<h1 class="docs-page-title">Réconciliation</h1>

<div class="docs-section">
    <p>
        L'onglet "Réconciliation" permet de vérifier la cohérence entre les deux serveurs NAS (ARCH et PAD).
        Il identifie les vidéos présentes sur un seul serveur et aide à maintenir la redondance des fichiers.
    </p>
</div>

<div class="docs-section">
    <h2>Principe de la réconciliation</h2>
    <p>
        BTSPlay fonctionne avec deux NAS complémentaires :
    </p>
    <ul>
        <li><strong>NAS ARCH</strong> : Archive en MXF (haute qualité, gros fichiers)</li>
        <li><strong>NAS PAD</strong> : Diffusion en MP4 (streaming web, fichiers optimisés)</li>
    </ul>

    <p>
        Idéalement, chaque vidéo doit exister dans les deux formats. La réconciliation vérifie cette règle
        et signale les incohérences.
    </p>

    <div class="docs-note">
        <p>
            <strong>Pourquoi c'est important :</strong> Si une vidéo n'existe qu'en MXF, elle ne peut pas
            être streamée. Si elle n'existe qu'en MP4, il n'y a pas de copie d'archive haute qualité.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Lancer une réconciliation</h2>

    <h3>Processus</h3>
    <ul>
        <li><strong>1. Cliquez sur "Lancer la réconciliation"</strong></li>
        <li><strong>2. Scan des deux NAS</strong> : Le système parcourt ARCH et PAD</li>
        <li><strong>3. Comparaison</strong> : Identification des fichiers manquants</li>
        <li><strong>4. Rapport</strong> : Affichage des résultats détaillés</li>
    </ul>

    <h3>Durée</h3>
    <p>
        La réconciliation peut prendre plusieurs minutes selon :
    </p>
    <ul>
        <li><strong>Nombre de fichiers</strong> : Plus il y a de vidéos, plus c'est long</li>
        <li><strong>Connexion réseau</strong> : Vitesse entre BTSPlay et les NAS</li>
        <li><strong>Charge serveur</strong> : Si les NAS sont très sollicités</li>
    </ul>

    <div class="docs-warning">
        <p>
            <strong>Attention :</strong> Ne lancez pas plusieurs réconciliations simultanément. Attendez
            que la précédente se termine avant d'en relancer une.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Interpréter les résultats</h2>

    <h3>Vidéos en ordre</h3>
    <p>
        Les vidéos présentes dans les deux formats apparaissent en vert avec une icône de validation.
    </p>

    <h3>MXF sans MP4</h3>
    <p>
        Ces vidéos existent sur NAS ARCH mais pas sur NAS PAD :
    </p>
    <ul>
        <li><strong>Cause</strong> : Le transcodage n'a pas encore été fait</li>
        <li><strong>Conséquence</strong> : La vidéo ne peut pas être streamée</li>
        <li><strong>Action</strong> : Lancez le transcodage depuis l'onglet "Transferts"</li>
    </ul>

    <h3>MP4 sans MXF</h3>
    <p>
        Ces vidéos existent sur NAS PAD mais pas sur NAS ARCH :
    </p>
    <ul>
        <li><strong>Cause</strong> : Fichier MXF supprimé ou jamais archivé</li>
        <li><strong>Conséquence</strong> : Pas de copie d'archive haute qualité</li>
        <li><strong>Action</strong> : Vérifiez l'origine du fichier, archivez le MXF source si disponible</li>
    </ul>

    <h3>Doublons</h3>
    <p>
        Plusieurs fichiers avec des noms similaires :
    </p>
    <ul>
        <li><strong>Cause</strong> : Uploads multiples, versions différentes</li>
        <li><strong>Conséquence</strong> : Confusion, espace disque gaspillé</li>
        <li><strong>Action</strong> : Identifiez la bonne version, supprimez les autres</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Actions correctives</h2>

    <h3>Pour les MXF sans MP4</h3>
    <ul>
        <li><strong>1. Notez les médias concernés</strong></li>
        <li><strong>2. Allez dans l'onglet "Transferts"</strong></li>
        <li><strong>3. Lancez le transcodage</strong> pour chaque vidéo</li>
        <li><strong>4. Attendez la fin du transcodage</strong></li>
        <li><strong>5. Synchronisez NAS PAD</strong> depuis l'onglet "Base de données"</li>
        <li><strong>6. Relancez la réconciliation</strong> pour vérifier</li>
    </ul>

    <h3>Pour les MP4 sans MXF</h3>
    <ul>
        <li><strong>1. Recherchez le fichier MXF source</strong> (bandes, disques durs externes)</li>
        <li><strong>2. Uploadez le MXF sur NAS ARCH</strong></li>
        <li><strong>3. Synchronisez NAS ARCH</strong> depuis l'onglet "Base de données"</li>
        <li><strong>4. Relancez la réconciliation</strong> pour vérifier</li>
    </ul>

    <h3>Pour les doublons</h3>
    <ul>
        <li><strong>1. Identifiez la version correcte</strong> (date, taille, qualité)</li>
        <li><strong>2. Supprimez les doublons</strong> via FTP ou explorateur NAS</li>
        <li><strong>3. Nettoyez la base de données</strong> (retirez les entrées obsolètes)</li>
        <li><strong>4. Relancez la réconciliation</strong> pour vérifier</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Bonnes pratiques</h2>

    <h3>Fréquence</h3>
    <ul>
        <li><strong>Hebdomadaire</strong> : Lancez une réconciliation chaque semaine</li>
        <li><strong>Après transcodages massifs</strong> : Vérifiez que tout s'est bien passé</li>
        <li><strong>Avant archivage</strong> : Assurez-vous que tout est en ordre</li>
    </ul>

    <h3>Workflow recommandé</h3>
    <ul>
        <li><strong>1. Lundi matin</strong> : Réconciliation hebdomadaire</li>
        <li><strong>2. Identifiez les MXF sans MP4</strong></li>
        <li><strong>3. Lancez les transcodages</strong> en batch</li>
        <li><strong>4. Vendredi</strong> : Vérifiez que tous les jobs sont terminés</li>
        <li><strong>5. Relancez la réconciliation</strong> pour validation finale</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Dépannage</h2>

    <h3>La réconciliation ne démarre pas</h3>
    <ul>
        <li>Vérifiez les connexions FTP aux deux NAS</li>
        <li>Consultez les logs système</li>
        <li>Testez manuellement l'accès aux serveurs</li>
    </ul>

    <h3>Résultats incohérents</h3>
    <ul>
        <li>Relancez la réconciliation</li>
        <li>Synchronisez d'abord les deux NAS</li>
        <li>Vérifiez que les fichiers existent réellement sur les serveurs</li>
    </ul>
</div>

<div class="docs-navigation-buttons">
    <a href="{{ route('docs.admin.transfers') }}" class="docs-nav-button">
        <i class="fa-solid fa-arrow-left"></i>
        Transferts
    </a>
    <a href="{{ route('docs.admin.settings') }}" class="docs-nav-button">
        Paramètres
        <i class="fa-solid fa-arrow-right"></i>
    </a>
</div>
@endsection

@extends('layouts.documentation')

@section('content')
<div class="docs-breadcrumb">
    <a href="{{ route('home') }}">Accueil</a>
    <span>/</span>
    <a href="{{ route('docs.index') }}">Documentation</a>
    <span>/</span>
    <span>Interface</span>
    <span>/</span>
    <span>Recherche</span>
</div>

<h1 class="docs-page-title">Recherche</h1>

<div class="docs-section">
    <p>
        Le système de recherche de BTSPlay vous permet de trouver rapidement des vidéos parmi l'ensemble
        de la collection. Il propose une recherche textuelle avec autocomplétion et des résultats pertinents
        basés sur plusieurs critères.
    </p>
</div>

<div class="docs-section">
    <h2>Accéder à la recherche</h2>
    <p>
        La barre de recherche est accessible en permanence depuis la barre de navigation en haut de chaque page.
    </p>

    <h3>Utilisation de base</h3>
    <ul>
        <li><strong>Cliquez dans le champ</strong> : Placez le curseur dans la barre de recherche</li>
        <li><strong>Saisissez votre requête</strong> : Tapez des mots-clés (titre, promotion, thématique, etc.)</li>
        <li><strong>Lancez la recherche</strong> :
            <ul style="padding-left: 2rem; margin-top: 0.5rem;">
                <li>Appuyez sur Entrée</li>
                <li>Cliquez sur l'icône loupe</li>
                <li>Sélectionnez une suggestion de l'autocomplétion</li>
            </ul>
        </li>
    </ul>
</div>

<div class="docs-section">
    <h2>Autocomplétion</h2>
    <p>
        Pendant la saisie, BTSPlay affiche des suggestions en temps réel pour vous aider à trouver
        rapidement ce que vous cherchez.
    </p>

    <h3>Fonctionnement</h3>
    <ul>
        <li><strong>Activation</strong> : Après 2 caractères saisis minimum</li>
        <li><strong>Délai</strong> : Les suggestions apparaissent après 300ms de pause dans la frappe</li>
        <li><strong>Nombre de résultats</strong> : Jusqu'à 5 suggestions affichées</li>
        <li><strong>Pertinence</strong> : Triées par score de correspondance</li>
    </ul>

    <h3>Contenu des suggestions</h3>
    <p>
        Chaque suggestion affiche :
    </p>
    <ul>
        <li><strong>Titre de la vidéo</strong> : En texte principal</li>
        <li><strong>Description courte</strong> : Extraits pertinents</li>
        <li><strong>Promotion</strong> : Année de réalisation</li>
        <li><strong>Miniature</strong> : Aperçu visuel (si disponible)</li>
    </ul>

    <h3>Navigation dans les suggestions</h3>
    <ul>
        <li><strong>Flèche bas/haut</strong> : Se déplacer dans la liste</li>
        <li><strong>Entrée</strong> : Sélectionner la suggestion en surbrillance</li>
        <li><strong>Échap</strong> : Fermer la liste de suggestions</li>
        <li><strong>Clic souris</strong> : Sélectionner directement une suggestion</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Astuce :</strong> L'autocomplétion fonctionne même si vous ne tapez qu'une partie
            du mot. Par exemple, "docum" suggérera les vidéos contenant "documentaire".
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Page de résultats</h2>
    <p>
        Après avoir lancé une recherche, vous êtes redirigé vers la page de résultats qui présente
        les vidéos correspondantes.
    </p>

    <h3>Structure de la page</h3>
    <ul>
        <li><strong>Barre de recherche</strong> : Pré-remplie avec votre requête</li>
        <li><strong>Nombre de résultats</strong> : Affichage du total trouvé</li>
        <li><strong>Grille de résultats</strong> : Vignettes des vidéos correspondantes</li>
        <li><strong>Pagination</strong> : Si plus de 16 résultats</li>
    </ul>

    <h3>Affichage des résultats</h3>
    <p>
        Les résultats sont présentés sous forme de grille, identique à la page d'accueil :
    </p>
    <ul>
        <li><strong>Vignettes cliquables</strong> : Accès direct aux pages de détail</li>
        <li><strong>Métadonnées visibles</strong> : Titre, description, promotion</li>
        <li><strong>Pertinence</strong> : Les résultats les plus pertinents en premier</li>
    </ul>

    <h3>Aucun résultat</h3>
    <p>
        Si aucune vidéo ne correspond à votre recherche :
    </p>
    <ul>
        <li><strong>Message explicite</strong> : "Aucune vidéo trouvée pour votre recherche"</li>
        <li><strong>Suggestions</strong> : Conseils pour affiner la recherche</li>
        <li><strong>Retour à l'accueil</strong> : Lien pour revenir à la grille complète</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Critères de recherche</h2>
    <p>
        La recherche BTSPlay porte sur plusieurs champs de métadonnées pour maximiser les chances
        de trouver ce que vous cherchez.
    </p>

    <h3>Champs indexés</h3>

    <h3>1. Titre</h3>
    <ul>
        <li><strong>Poids maximal</strong> : Correspondance dans le titre = haute pertinence</li>
        <li><strong>Recherche partielle</strong> : Trouve les mots-clés même incomplets</li>
        <li><strong>Insensible à la casse</strong> : Majuscules/minuscules ignorées</li>
    </ul>

    <h3>2. Description</h3>
    <ul>
        <li><strong>Recherche en texte intégral</strong> : Parcourt toute la description</li>
        <li><strong>Poids moyen</strong> : Moins prioritaire que le titre</li>
        <li><strong>Extraits affichés</strong> : Les passages pertinents sont mis en avant</li>
    </ul>

    <h3>3. Promotion</h3>
    <ul>
        <li><strong>Format</strong> : Année ou format "2023-2024"</li>
        <li><strong>Recherche exacte</strong> : Trouvez toutes les vidéos d'une promotion</li>
        <li><strong>Exemples</strong> : "2024", "2023-2024", "promo 2024"</li>
    </ul>

    <h3>4. Type de projet</h3>
    <ul>
        <li><strong>Catégories</strong> : Fiction, documentaire, reportage, clip, etc.</li>
        <li><strong>Recherche par mot-clé</strong> : Tapez "fiction" pour tous les projets de fiction</li>
    </ul>

    <h3>5. Thématiques</h3>
    <ul>
        <li><strong>Tags personnalisés</strong> : Définis par les professeurs</li>
        <li><strong>Multi-tags</strong> : Une vidéo peut avoir plusieurs thématiques</li>
        <li><strong>Exemples</strong> : "nature", "société", "portrait", etc.</li>
    </ul>

    <h3>6. Propriétés personnalisées</h3>
    <ul>
        <li><strong>Champs spécifiques</strong> : Ajoutés par les administrateurs</li>
        <li><strong>Recherche flexible</strong> : Tous les champs custom sont indexés</li>
    </ul>

    <div class="docs-note">
        <p>
            <strong>Note technique :</strong> La recherche utilise un algorithme de pertinence qui
            pondère les résultats. Une correspondance dans le titre sera toujours plus prioritaire
            qu'une correspondance dans la description.
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Astuces de recherche</h2>

    <h3>Recherche simple</h3>
    <ul>
        <li><strong>Un seul mot</strong> : Trouve toutes les occurrences de ce mot</li>
        <li><strong>Plusieurs mots</strong> : Trouve les vidéos contenant tous ces mots (ET logique)</li>
        <li><strong>Mots partiels</strong> : "docum" trouvera "documentaire"</li>
    </ul>

    <h3>Exemples de recherches</h3>
    <ul>
        <li><strong>"nature"</strong> : Toutes les vidéos ayant "nature" dans leurs métadonnées</li>
        <li><strong>"2024"</strong> : Toutes les vidéos de la promotion 2024</li>
        <li><strong>"fiction 2023"</strong> : Fictions de la promotion 2023</li>
        <li><strong>"documentaire société"</strong> : Documentaires sur des thèmes sociétaux</li>
    </ul>

    <h3>Recherche par participant (à venir)</h3>
    <div class="docs-warning">
        <p>
            <strong>Fonctionnalité en développement :</strong> Dans les versions futures, vous pourrez
            rechercher des vidéos par nom de participant (réalisateur, cadreur, etc.).
        </p>
    </div>
</div>

<div class="docs-section">
    <h2>Optimiser vos recherches</h2>

    <h3>Soyez spécifique</h3>
    <ul>
        <li>Utilisez des mots-clés précis plutôt que des termes génériques</li>
        <li>Combinez plusieurs critères pour affiner les résultats</li>
        <li>Utilisez l'autocomplétion pour découvrir les termes disponibles</li>
    </ul>

    <h3>Profitez de l'autocomplétion</h3>
    <ul>
        <li>Commencez à taper et consultez les suggestions</li>
        <li>Les suggestions montrent les termes exacts utilisés dans la base</li>
        <li>Gagnez du temps en sélectionnant directement une suggestion</li>
    </ul>

    <h3>Explorez les résultats</h3>
    <ul>
        <li>Parcourez plusieurs pages de résultats si nécessaire</li>
        <li>Les métadonnées visibles sur les vignettes peuvent vous aider à affiner</li>
        <li>N'hésitez pas à reformuler votre requête si les résultats ne conviennent pas</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Limitations actuelles</h2>
    <p>
        Le système de recherche actuel présente quelques limitations qui seront améliorées dans
        les versions futures :
    </p>
    <ul>
        <li><strong>Pas de recherche floue</strong> : Les fautes de frappe ne sont pas tolérées</li>
        <li><strong>Pas de filtres avancés</strong> : Impossible de filtrer par date, durée, etc.</li>
        <li><strong>Pas de recherche par participant</strong> : Les noms d'étudiants ne sont pas indexés</li>
        <li><strong>Pas de recherche par projet</strong> : Impossible de chercher un projet spécifique</li>
    </ul>
</div>

<div class="docs-section">
    <h2>Performance</h2>

    <h3>Vitesse de recherche</h3>
    <ul>
        <li><strong>Base de données indexée</strong> : Recherche instantanée même sur de grandes collections</li>
        <li><strong>Cache côté client</strong> : Les suggestions sont mises en cache temporairement</li>
        <li><strong>Optimisation serveur</strong> : Requêtes SQL optimisées avec LIKE</li>
    </ul>

    <h3>Autocomplétion en temps réel</h3>
    <ul>
        <li><strong>Requêtes AJAX</strong> : Communication asynchrone avec le serveur</li>
        <li><strong>Debouncing</strong> : Évite de surcharger le serveur pendant la frappe</li>
        <li><strong>Limitation de résultats</strong> : Maximum 5 suggestions pour la rapidité</li>
    </ul>
</div>

<div class="docs-navigation-buttons">
    <a href="{{ route('docs.interface.video-player') }}" class="docs-nav-button">
        <i class="fa-solid fa-arrow-left"></i>
        Lecteur vidéo
    </a>
    <a href="{{ route('docs.admin.overview') }}" class="docs-nav-button">
        Admin - Vue d'ensemble
        <i class="fa-solid fa-arrow-right"></i>
    </a>
</div>
@endsection

@extends('layouts.app')

{{-- Suppression des balises HTML structurelles et inclusion des styles et scripts --}}
@push('styles')
    <link href="{{ asset('ressources/Style/pageAdministration.css') }}" rel="stylesheet">
    <link href="{{ asset('ressources/Style/transfert.css') }}" rel="stylesheet">
    <link href="{{ asset('ressources/Style/sauvegarde.css') }}" rel="stylesheet">
    <link href="{{ asset('ressources/lib/Swiper/swiper-bundle.min.css') }}" rel="stylesheet">
@endpush

@section('content')
    {{-- ATTENTION : Le bloc PHP initial (session_start, require_once, et l'appel des contrôleurs) a été retiré. 
        Toutes les variables ($logsGeneraux, $listeProfesseurs, $tabDernieresVideos, etc.) et les constantes 
        DOIVENT être passées à la vue par ton Contrôleur. --}}

    {{-- Inclusion de la popup --}}
    @include('popup')

    <div><h1>Administration de BTSPlay</h1></div>
    <div class="tabs">
        <div class="tab" data-tab="database">Base de données</div>
        <div class="tab" data-tab="reconciliation">Réconciliation</div>
        <div class="tab" data-tab="transfert">Fonction de transfert</div>
        <div class="tab" data-tab="settings">Paramétrage du site</div>
        <div class="tab" data-tab="logs">Consulter les logs</div>
        
        {{-- Logique pour cacher l'onglet utilisateur si pas Admin --}}
        @if(session('role') == ROLE_ADMINISTRATEUR)
            <div class="tab" data-tab="users">Gérer les utilisateurs</div>
        @endif
    </div>
    
    <div class="tab-content database" id="database">
        <h2>Sauvegarde de la base de données</h2>
        <div id="container-saveLog">
            <div id="container-sauvegarde">
                <div class="content">
                    <h3>Paramètre des sauvegardes</h3>
                    
                    <div class="form-group">
                        <label for="tempsLancement">Choisir l'heure d'exécution :</label>
                        <input type="time" id="tempsLancement" class="sauvegardeInputs" value="00:00"/>
                    </div>
                    
                    <div class="form-group">
                        <label for="select_Day">Choisir le jour d'exécution :</label>
                        <select name="day" id="select_Day" class="sauvegardeInputs">
                            <option value="*" selected>Tous les jours</option>
                            <option value="1">Lundi</option>
                            <option value="2">Mardi</option>
                            <option value="3">Mercredi</option>
                            <option value="4">Jeudi</option>
                            <option value="5">Vendredi</option>
                            <option value="6">Samedi</option>
                            <option value="0">Dimanche</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="select_Month">Choisir le mois d'exécution :</label>
                        <select name="month" id="select_Month" class="sauvegardeInputs">
                            <option value="*" selected>Tous les mois</option>
                            <option value="1">Janvier</option>
                            <option value="2">Février</option>
                            <option value="3">Mars</option>
                            <option value="4">Avril</option>
                            <option value="5">Mai</option>
                            <option value="6">Juin</option>
                            <option value="7">Juillet</option>
                            <option value="8">Août</option>
                            <option value="9">Septembre</option>
                            <option value="10">Octobre</option>
                            <option value="11">Novembre</option>
                            <option value="12">Décembre</option>
                        </select>
                    </div>
                
                    <div class="btn-sauvegarde-container">
                        <button onClick="changeDatabaseSaveTime()" class="btn btnRouge">Enregistrer les paramètres</button>
                        <button onClick="createDatabaseSave()" class="btn btnJaune">Réaliser une sauvegarde manuelle</button>
                    </div>
                </div>
            </div>

            <div class="right-panel">
                <div class="log-container colonne-2">
                    {{-- Affichage des logs Sauvegardes BDD --}}
                    @foreach ($logsSauvegardesBDD as $line)
                        <div class="log-line">{{ $line }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- TAB: Réconciliation --}}
    <div class="tab-content" id="reconciliation">
        <div class="reconciliation-section">
            <h2 class="section-title">Fonction de réconciliation</h2>
        </div>

        {{-- Affichage du résultat de la réconciliation --}}
        @if (session('reconciliation_result'))
            <div class="result-section">
                <div class="reconciliation-result">
                    @php 
                    echo session('reconciliation_result'); 
                    session()->forget('reconciliation_result'); // Nettoyer après affichage
                    @endphp
                </div>
            </div>
        @endif
        
        <form method="post" class="reconciliation-form">
            @csrf
            <input type="hidden" name="action" value="declencherReconciliation">
            <button type="submit" class="reconciliation-button">Lancer la réconciliation</button>
        </form>
    </div>

    {{-- TAB: Transfert --}}
    <div class="tab-content" id="transfert">
        <h2>Fonction de transfert</h2>
        <div class="container">
            <div class="content-wrapper">
                <div class="header-row">
                    <div class="transfers-header">
                        <h2>Transferts</h2>
                    </div>
                    <div class="pending-videos-header">
                        <h2>Vidéos en attente de métadonnées</h2>
                    </div>
                </div>

                <div class="content-row">
                    <div class="lignes-container">
                        <div class="lignes"></div>
                        <div class="button-container">
                            <button class="btn" id="btnConversion" onclick="lancerConversion()">Lancer conversion</button>
                        </div> 
                    </div>
                    <div class="symbol-container">
                        <img src='{{ asset('ressources/Images/avance-rapide.png') }}' alt="Symbole de transfert">
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Fichier</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Affichage des dernières vidéos transférées --}}
                                @foreach ($tabDernieresVideos as $video)
                                    <tr>
                                        <td><a href="{{ route('video.show', ['v' => $video['id']]) }}">{{ $video['date_creation'] }}</a></td>
                                        <td><a href="{{ route('video.show', ['v' => $video['id']]) }}">{{ $video['mtd_tech_titre'] }}</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TAB: Paramètres (Settings) --}}
    <div class="tab-content" id="settings">
        <h2>Paramétrage</h2>
        
        <div class="table-of-contents">
            <h3>Table des Matières</h3>
            <ul>
                <li><a href="#section-uris">URIs</a></li>
                <li><a href="#section-ftp">Connexions FTP</a></li>
                <li><a href="#section-bd">Base de données</a></li>
                <li><a href="#sauvegarde">Sauvegarde</a></li>
                <li><a href="#section-logs">Logs</a></li>
                <li><a href="#section-multiprocessing">Multiprocessing</a></li>
                <li><a href="#personnalisation">Personnalisation</a></li>
            </ul>
        </div>

        <form method="post" action="#" class="form-container" onsubmit="modifierParametres(event);">
            @csrf {{-- J'ajoute le token CSRF --}}
            <input type="hidden" name="action" value="mettreAJourParametres">

            {{-- Section URIs --}}
            <h3 id="section-uris" class="section-title">URIs</h3>
            <div>
                {{-- Tous les inputs utilisent la syntaxe Blade pour afficher les CONSTANTES --}}
                <label for="uri_racine_nas_pad" class="form-label">URI racine du NAS PAD:</label>
                <input type="text" id="uri_racine_nas_pad" name="uri_racine_nas_pad" value="{{ URI_RACINE_NAS_PAD }}" 
                    oninput="validerURI('uri_racine_nas_pad')" pattern="^\S+$" title="Les espaces ne sont pas autorisés" class="form-input" required><br><br>
                
                <label for="uri_racine_nas_arch" class="form-label">URI racine du NAS ARCH:</label>
                <input type="text" id="uri_racine_nas_arch" name="uri_racine_nas_arch" value="{{ URI_RACINE_NAS_ARCH }}" 
                    oninput="validerURI('uri_racine_nas_arch')" pattern="^\S+$" title="Les espaces ne sont pas autorisés" class="form-input" required><br><br>
                
                <label for="uri_racine_stockage_local" class="form-label">URI racine du stockage local:</label>
                <input type="text" id="uri_racine_stockage_local" name="uri_racine_stockage_local" value="{{ URI_RACINE_STOCKAGE_LOCAL }}" 
                    pattern="^\S+$" title="Les espaces ne sont pas autorisés" class="form-input" required><br><br>
                
                <label for="uri_racine_nas_diff" class="form-label">URI racine du NAS DIFF:</label>
                <input type="text" id="uri_racine_nas_diff" name="uri_racine_nas_diff" value="{{ URI_RACINE_NAS_DIFF }}" 
                    oninput="validerURI('uri_racine_nas_diff')" pattern="^\S+$" title="Les espaces ne sont pas autorisés" class="form-input" required>
            </div>

            {{-- Section Connexions FTP --}}
            <h3 id="section-ftp" class="section-title">Connexions FTP</h3>
            <div>
                <h4>NAS PAD</h4>
                <label for="nas_pad" class="form-label">IP du serveur:</label>
                <input type="text" id="nas_pad" name="nas_pad" value="{{ NAS_PAD }}" pattern="^\S+$" title="Les espaces ne sont pas autorisés" class="form-input" required><br><br>
                
                {{-- ... Tous les autres inputs FTP/BD/Logs/Sauvegarde sont convertis de cette manière, en utilisant {{ CONSTANTE }} pour la value --}}
                {{-- J'ajoute le premier bloc de mot de passe comme exemple --}}
                <label for="login_nas_pad" class="form-label">Identifiant:</label>
                <input type="text" id="login_nas_pad" name="login_nas_pad" value="{{ LOGIN_NAS_PAD }}" pattern="^\S+$" title="Les espaces ne sont pas autorisés" class="form-input" required><br><br>
                
                <label for="password_nas_pad" class="form-label">Mot de passe:</label>
                <div class="input-with-icon">
                    <input type="password" id="password_nas_pad" name="password_nas_pad" value="{{ PASSWORD_NAS_PAD }}" pattern="^\S+$" title="Les espaces ne sont pas autorisés" class="form-input" required>
                    <button type="button" onclick="afficherMotDePasse('password_nas_pad', 'eye_pad')" class="password-toggle-button">
                        <img id="eye_pad" src="{{ asset('ressources/Images/eye-closed.png') }}" alt="Afficher/Masquer" class="eye-icon">
                    </button>
                </div><br>
                {{-- Suite des inputs FTP (NAS PAD SUP, NAS ARCH, NAS DIFF) --}}
                
                {{-- Section Logs (un exemple de checkbox) --}}
                <h3 id="section-logs" class="section-title">Logs</h3>
                <label for="nb_lignes_logs" class="form-label">Nombre de lignes de logs maximal:</label>
                <input type="number" id="nb_lignes_logs" name="nb_lignes_logs" min=0 value="{{ NB_LIGNES_LOGS }}" class="form-input" required><br><br>
                <div class='logRecent'>
                    <label for="affichage_logs_plus_recents_premiers" class="form-label">Afficher les logs les plus récents en premier:</label>
                    <input type="checkbox" id="affichage_logs_plus_recents_premiers" name="affichage_logs_plus_recents_premiers" {{ AFFICHAGE_LOGS_PLUS_RECENTS_PREMIERS=='on' ? 'checked' : '' }} class="checkbox-input" required>
                </div>

                {{-- ... Ajoutez ici le reste de vos inputs pour les sections BD, Sauvegarde, Multiprocessing, Personnalisation... --}}
            </div>
            
            <input type="submit" value="Mettre à jour" class="submit-button">
        </form>

    </div>

    {{-- TAB: Logs --}}
    <div class="tab-content" id="logs">
        <h2>Consulter les logs</h2>
        <div class="log-container">
            {{-- Affichage des logs généraux --}}
            @foreach ($logsGeneraux as $line)
                <div class="log-line">{{ $line }}</div>
            @endforeach
        </div>
    </div>

    {{-- TAB: Gérer les utilisateurs (Admin seulement) --}}
    @if(session('role') == ROLE_ADMINISTRATEUR)
        <div class="tab-content" id="users">
            <h2>Gérer les utilisateurs</h2>
            <table>
                <tr>
                    <th></th>
                    <th>Modifier la vidéo</th>
                    <th>Diffuser la vidéo</th>
                    <th>Supprimer la vidéo</th>
                    <th>Administrer le site</th>
                </tr>
                @foreach($listeProfesseurs as $professeur)
                    <tr>
                        @php 
                        if ($professeur["role"] == ROLE_ADMINISTRATEUR) {
                            $desactivation = "disabled";
                            $class = "class='gris'";
                        } else {
                            $desactivation = "";
                            $class = "";
                        }
                        @endphp

                        <th {!! $class !!}>{{ $professeur['nom'] . " " . $professeur['prenom'] }}</th>

                        <td>
                            <input {{ $desactivation }} type="checkbox" data-prof="{{ $professeur["professeur"] }} " data-colonne="modifier" {{ $professeur["modifier"] == 1 ? "checked" : "" }}/>
                        </td>
                        <td>
                            <input {{ $desactivation }} type="checkbox" data-prof="{{ $professeur["professeur"] }}" data-colonne="diffuser" {{ $professeur["diffuser"] == 1 ? "checked" : "" }}/>
                        </td>
                        <td>
                            <input {{ $desactivation }} type="checkbox" data-prof="{{ $professeur["professeur"] }}" data-colonne="supprimer" {{ $professeur["supprimer"] == 1 ? "checked" : "" }}/>
                        </td>
                        <td>
                            <input {{ $desactivation }} type="checkbox" data-prof="{{ $professeur["professeur"] }}" data-colonne="administrer" {{ $professeur["administrer"] == 1 ? "checked" : "" }}/>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
    
@endsection

@push('scripts')
    <script src="{{ asset('ressources/lib/Swiper/swiper-bundle.min.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            affichageLogsCouleurs();
            gestionOngletsAdministration();
            appelScanVideo();
            detectionCheckboxes(); 
        });
    </script>
@endpush
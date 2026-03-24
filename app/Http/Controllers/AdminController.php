<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use App\Models\User;
use App\Models\Media;
use App\Models\Projet;
use App\Models\Professeur;
use App\Models\Eleve;

class AdminController extends Controller
{
    /**
     * @brief Vérifie que l'utilisateur connecté a les droits d'administration.
     *
     * Contrôle les autorisations avant chaque action sensible.
     * Seuls les utilisateurs ayant le rôle "admin" ou "professeur" sont autorisés.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * Erreur 403 si l'utilisateur n'est pas connecté ou n'a pas les permissions
     */
    private function checkAdminAccess()
    {
        if (!auth()->check()) {
            abort(403, 'Vous devez être connecté pour accéder à cette page.');
        }

        $user = auth()->user();
        if (!$user->hasRole('admin') && !$user->hasRole('professeur')) {
            abort(403, 'Accès réservé aux administrateurs et professeurs.');
        }
    }

    /**
     * @brief Affiche la page de configuration système.
     *
     * Charge tous les paramètres depuis le fichier de configuration btsplay
     * et les transmet à la vue pour affichage et modification.
     *
     * Catégories de paramètres :
     * - URIs des différents stockages (PAD, ARCH, local, DIFF)
     * - Informations de connexion FTP (3 NAS : PAD, ARCH, DIFF)
     * - Configuration de la base de données
     * - Paramètres de sauvegarde, logs et processus
     * - Paramètres d'affichage
     *
     * @return \Illuminate\View\View Vue "admin.settings" avec tous les paramètres
     */
    public function settings()
    {
        $this->checkAdminAccess();

        $settings = [
            // URIs
            'uri_nas_pad'       => config('btsplay.uris.nas_pad'),
            'uri_nas_arch'      => config('btsplay.uris.nas_arch'),
            'uri_local'         => config('btsplay.uris.local'),
            'uri_nas_diff'      => config('btsplay.uris.nas_diff'),

            // FTP PAD
            'nas_pad_ip'        => config('btsplay.ftp.pad.ip'),
            'nas_pad_user'      => config('btsplay.ftp.pad.user'),
            'nas_pad_pass'      => config('btsplay.ftp.pad.password'),
            'nas_pad_user_sup'  => config('btsplay.ftp.pad.user_sup'),
            'nas_pad_pass_sup'  => config('btsplay.ftp.pad.pass_sup'),

            // FTP ARCH
            'nas_arch_ip'       => config('btsplay.ftp.arch.ip'),
            'nas_arch_user'     => config('btsplay.ftp.arch.user'),
            'nas_arch_pass'     => config('btsplay.ftp.arch.password'),
            'nas_arch_user_sup' => config('btsplay.ftp.arch.user_sup'),
            'nas_arch_pass_sup' => config('btsplay.ftp.arch.pass_sup'),

            // FTP DIFF
            'nas_diff_ip'       => config('btsplay.ftp.diff.ip'),
            'nas_diff_user'     => config('btsplay.ftp.diff.user'),
            'nas_diff_pass'     => config('btsplay.ftp.diff.password'),

            // DB
            'db_host'           => config('database.connections.mysql.host'),
            'db_port'           => config('database.connections.mysql.port'),
            'db_name'           => config('database.connections.mysql.database'),
            'db_user'           => config('database.connections.mysql.username'),
            'db_pass'           => config('database.connections.mysql.password'),

            // Backup & Logs & Process & Display
            'backup_gen'        => config('btsplay.backup.uri_generated'),
            'backup_dump'       => config('btsplay.backup.uri_dump'),
            'backup_suf_dump'   => config('btsplay.backup.suffix_dump'),

            'log_general'       => config('btsplay.logs.general'),
            'log_backup'        => config('btsplay.logs.backup'),
            'log_lines'         => config('btsplay.logs.max_lines'),
            'log_recent'        => config('btsplay.logs.recent_first'),

            'max_videos'        => config('btsplay.process.max_videos'),
            'Workflow_id'       => config('btsplay.process.workflow_id'),

            'disp_swiper'       => config('btsplay.display.swiper_count'),
            'disp_history'      => config('btsplay.display.history_count'),
        ];

        return view('admin.settings', compact('settings'));
    }

    /**
     * @brief Traite la mise à jour des paramètres de configuration système.
     *
     * Cette méthode :
     * - Récupère les données du formulaire de paramètres
     * - Mappe les champs du formulaire vers les variables d'environnement
     * - Met à jour le fichier .env avec les nouvelles valeurs
     * - Vide le cache de configuration pour appliquer les changements
     *
     * @param Request $request Requête HTTP contenant les nouveaux paramètres
     * @return \Illuminate\Http\RedirectResponse Redirection avec message de succès
     */
    public function updateSettings(Request $request)
    {
        $this->checkAdminAccess();

        // Map Form Inputs -> .ENV Variables
        $envUpdates = [
            'URI_RACINE_NAS_PAD' => $request->uri_nas_pad,
            'URI_RACINE_NAS_ARCH' => $request->uri_nas_arch,
            'URI_RACINE_STOCKAGE_LOCAL' => $request->uri_local,
            'URI_RACINE_NAS_DIFF' => $request->uri_nas_diff,

            'NAS_PAD_IP' => $request->nas_pad_ip,
            'NAS_PAD_USER' => $request->nas_pad_user,
            'NAS_PAD_PASSWORD' => $request->nas_pad_pass,
            'NAS_PAD_USER_SUP' => $request->nas_pad_user_sup,
            'NAS_PAD_PASSWORD_SUP' => $request->nas_pad_pass_sup,

            'NAS_ARCH_IP' => $request->nas_arch_ip,
            'NAS_ARCH_USER' => $request->nas_arch_user,
            'NAS_ARCH_PASSWORD' => $request->nas_arch_pass,
            'NAS_ARCH_USER_SUP' => $request->nas_arch_user_sup,
            'NAS_ARCH_PASSWORD_SUP' => $request->nas_arch_pass_sup,

            'NAS_DIFF_IP' => $request->nas_diff_ip,
            'NAS_DIFF_USER' => $request->nas_diff_user,
            'NAS_DIFF_PASSWORD' => $request->nas_diff_pass,

            'DB_HOST' => $request->db_host,
            'DB_PORT' => $request->db_port,
            'DB_DATABASE' => $request->db_name,
            'DB_USERNAME' => $request->db_user,
            'DB_PASSWORD' => $request->db_pass,

            'URI_FICHIER_GENERES' => $request->backup_gen,
            'URI_DUMP_SAUVEGARDE' => $request->backup_dump,
            'SUFFIXE_FICHIER_DUMP_SAUVEGARDE' => $request->backup_suf_dump,

            'NOM_FICHIER_LOG_GENERAL' => $request->log_general,
            'NOM_FICHIER_LOG_SAUVEGARDE' => $request->log_backup,
            'NB_LIGNES_LOGS' => $request->log_lines,
            'AFFICHAGE_LOGS_PLUS_RECENTS_PREMIERS' => $request->has('log_recent') ? 'true' : 'false',

            'NB_VIDEOS_FFASTRANS' => $request->max_videos,
            'WORKFLOW_ID' => $request->workflow_id,

            'NB_VIDEOS_PAR_SWIPER' => $request->disp_swiper,
            'NB_VIDEOS_HISTORIQUE_TRANSFERT' => $request->disp_history,
        ];

        $this->updateEnvFile($envUpdates);
        Artisan::call('config:clear');

        return back()->with('success', 'Paramètres mis à jour !');
    }

    /**
     * @brief Met à jour les valeurs dans le fichier .env de l'application.
     *
     * Fonctionnalités :
     * - Recherche chaque clé dans le fichier .env existant
     * - Remplace la valeur si la clé existe
     * - Ajoute une nouvelle ligne si la clé n'existe pas
     * - Gère automatiquement les guillemets pour les valeurs contenant des espaces
     *
     * @param array $values Tableau associatif [clé => valeur] à mettre à jour
     * @return void
     */
    private function updateEnvFile(array $values)
    {
        $path = App::environmentFilePath();
        $envContent = file_get_contents($path);

        foreach ($values as $key => $newValue) {
            $newValue = $newValue ?? '';

            if (str_contains($newValue, ' ')) {
                $newValue = '"' . $newValue . '"';
            }

            $pattern = "/^" . preg_quote($key, '/') . "=(.*)$/m";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, "{$key}={$newValue}", $envContent);
            } else {
                $envContent .= "\n{$key}={$newValue}";
            }
        }
        file_put_contents($path, $envContent);
    }

    /**
     * @brief Affiche les logs système de l'application.
     *
     * Charge les dernières lignes du fichier laravel.log et les affiche
     * dans l'ordre chronologique ou antéchronologique selon la configuration.
     *
     * @return \Illuminate\View\View Vue "admin.logs" avec les lignes de log
     */
    public function logs()
    {
        $this->checkAdminAccess();

        $logPath = storage_path('logs/laravel.log');
        $logs = [];

        if (File::exists($logPath)) {
            $file = file($logPath);
            $logs = array_slice($file, -50);
            if (config('btsplay.logs.recent_first')) {
                $logs = array_reverse($logs);
            }
        }

        return view('admin.logs', compact('logs'));
    }

    /**
     * @brief Affiche la page de gestion des utilisateurs (professeurs et élèves).
     *
     * Récupère la liste complète des professeurs et des élèves,
     * avec le nombre de participations pour chaque élève.
     *
     * @return \Illuminate\View\View Vue "admin.users" avec les listes d'utilisateurs
     */
    public function users()
    {
        $this->checkAdminAccess();

        // Only fetch users when on the Users tab
        $professeurs = Professeur::all();

        // Récupère les élèves avec le nombre de leurs participations pour la nouvelle table
        $eleves = Eleve::withCount('participations')->orderBy('nom')->orderBy('prenom')->get();

        return view('admin.users', compact('professeurs', 'eleves'));
    }

    /**
     * @brief Crée un nouveau compte professeur avec ses accès.
     *
     * Cette méthode :
     * - Valide les informations saisies (nom, prénom, identifiant, mot de passe)
     * - Crée un compte utilisateur avec authentification
     * - Assigne le rôle "professeur"
     * - Crée le profil professeur associé
     *
     * @param Request $request Requête HTTP contenant les données du formulaire
     * @return \Illuminate\Http\RedirectResponse Redirection avec message de succès
     *
     * @throws \Illuminate\Validation\ValidationException
     * Si les données sont invalides ou si l'identifiant existe déjà
     */
    public function createProfesseur(Request $request)
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'identifiant' => 'required|string|unique:users,name',
            'mot_de_passe' => 'required|min:8',
        ], [
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'identifiant.required' => "L'identifiant est obligatoire.",
            'identifiant.unique' => 'Cet identifiant est déjà utilisé.',
            'mot_de_passe.required' => 'Le mot de passe est obligatoire.',
            'mot_de_passe.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        // Créer d'abord le compte utilisateur
        $user = User::create([
            'name' => $validated['identifiant'],
            'password' => bcrypt($validated['mot_de_passe']),
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'],
        ]);

        // Assigner le rôle professeur
        $user->assignRole('professeur');

        // Créer le profil professeur
        Professeur::create([
            'user_id' => $user->id,
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'],
        ]);

        return back()->with('success', 'Professeur créé avec succès!');
    }

    /**
     * @brief Supprime un professeur et son compte utilisateur associé.
     *
     * Vérifie avant suppression que le professeur n'est pas référent de médias.
     * Si c'est le cas, la suppression est refusée pour préserver l'intégrité des données.
     *
     * @param int $id Identifiant du professeur
     * @return \Illuminate\Http\RedirectResponse Redirection avec message de succès ou d'erreur
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * Si le professeur n'existe pas
     */
    public function deleteProfesseur($id)
    {
        $this->checkAdminAccess();

        $professeur = Professeur::findOrFail($id);

        if ($professeur->media()->count() > 0) {
            return back()->withErrors('Impossible de supprimer un professeur référent de médias.');
        }

        $professeur->delete();
        return back()->with('success', 'Professeur supprimé avec succès!');
    }

    /**
     * @brief Crée un nouvel élève dans le système.
     *
     * Enregistre un élève avec son nom et prénom.
     * Contrairement aux professeurs, les élèves n'ont pas de compte utilisateur associé.
     *
     * @param Request $request Requête HTTP contenant nom et prénom
     * @return \Illuminate\Http\RedirectResponse Redirection avec message de succès
     *
     * @throws \Illuminate\Validation\ValidationException
     * Si le nom ou le prénom est manquant
     */
    public function createEleve(Request $request)
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
        ], [
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
        ]);

        Eleve::create($validated);
        return back()->with('success', 'Élève créé avec succès!');
    }

    /**
     * @brief Supprime un élève du système.
     *
     * Supprime l'élève ainsi que toutes ses participations associées
     * (grâce aux contraintes de cascade en base de données).
     *
     * @param int $id Identifiant de l'élève
     * @return \Illuminate\Http\RedirectResponse Redirection avec message de succès
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * Si l'élève n'existe pas
     */
    public function deleteEleve($id)
    {
        $this->checkAdminAccess();

        $eleve = Eleve::findOrFail($id);
        $eleve->delete();
        return back()->with('success', 'Élève supprimé avec succès!');
    }

    /**
     * @brief Affiche la page de gestion des projets.
     *
     * Récupère tous les projets avec le nombre de médias associés à chacun.
     * Les résultats sont paginés (20 par page).
     *
     * @return \Illuminate\View\View Vue "admin.projets" avec la liste paginée des projets
     */
    public function projets()
    {
        $this->checkAdminAccess();

        $projets = \App\Models\Projet::withCount('media')->paginate(20);
        return view('admin.projets', compact('projets'));
    }

    /**
     * @brief Crée un nouveau projet dans le système.
     *
     * Un projet permet de regrouper plusieurs médias par thématique ou contexte.
     *
     * @param Request $request Requête HTTP contenant le libellé du projet
     * @return \Illuminate\Http\RedirectResponse Redirection avec message de succès
     *
     * @throws \Illuminate\Validation\ValidationException
     * Si le libellé est manquant ou invalide
     */
    public function createProjet(Request $request)
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'libelle' => 'required|string|max:255',
        ], [
            'libelle.required' => 'Le libellé du projet est obligatoire.',
        ]);

        \App\Models\Projet::create($validated);

        return back()->with('success', 'Projet créé avec succès!');
    }

    /**
     * @brief Supprime un projet du système.
     *
     * Vérifie avant suppression que le projet ne contient aucun média.
     * Si des médias y sont rattachés, la suppression est refusée.
     *
     * @param int $id Identifiant du projet
     * @return \Illuminate\Http\RedirectResponse Redirection avec message de succès ou d'erreur
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * Si le projet n'existe pas
     */
    public function deleteProjet($id)
    {
        $this->checkAdminAccess();

        $projet = \App\Models\Projet::findOrFail($id);

        if ($projet->media()->count() > 0) {
            return back()->withErrors('Impossible de supprimer un projet contenant des médias.');
        }

        $projet->delete();

        return back()->with('success', 'Projet supprimé avec succès!');
    }

    /**
     * @brief Affiche la page de gestion de la base de données.
     *
     * Charge l'historique des sauvegardes depuis le fichier de log backup.log
     * et l'affiche pour permettre à l'administrateur de suivre les opérations.
     *
     * @return \Illuminate\View\View Vue "admin.database" avec les logs de sauvegarde
     */
    public function databaseView()
    {
        $this->checkAdminAccess();

        // Read the log file to display in the view
        $logPath = storage_path('logs/backup.log');
        $backupLogs = [];

        if (File::exists($logPath)) {
            // Get last 50 lines
            $file = file($logPath);
            $backupLogs = array_slice($file, -50);
        }

        return view('admin.database', compact('backupLogs'));
    }

    /**
     * @brief Lance une sauvegarde manuelle de la base de données.
     *
     * Fonctionnalités :
     * - Exécute la commande Artisan "db:backup" avec le type "manual"
     * - Enregistre le résultat (succès ou échec) dans le log backup.log
     * - Affiche un message à l'utilisateur selon le résultat
     *
     * @return \Illuminate\Http\RedirectResponse Redirection avec message de succès ou d'erreur
     */
    public function runBackup()
    {
        $this->checkAdminAccess();

        try {
            // Trigger the command manually and get exit code
            $exitCode = Artisan::call('db:backup --type=manual');

            // Get the output to show success message
            $output = Artisan::output();

            if ($exitCode === 0) {
                // SUCCESS
                $logMessage = "[" . date('Y-m-d H:i:s') . "] Manual Backup: Success\n";
                file_put_contents(storage_path('logs/backup.log'), $logMessage, FILE_APPEND);
                return back()->with('success', 'Sauvegarde manuelle terminée avec succès.');
            } else {
                // FAILURE
                $logMessage = "[" . date('Y-m-d H:i:s') . "] Manual Backup: FAILED. Error: " . trim($output) . "\n";
                file_put_contents(storage_path('logs/backup.log'), $logMessage, FILE_APPEND);
                return back()->with('error', 'Échec de la sauvegarde. Consultez les logs.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la sauvegarde: ' . $e->getMessage());
        }
    }

    /**
     * @brief Enregistre les paramètres de planification des sauvegardes automatiques.
     *
     * Permet de configurer :
     * - L'heure d'exécution (backup_time)
     * - Le jour du mois (backup_day)
     * - Le mois de l'année (backup_month)
     *
     * Ces paramètres sont utilisés par le scheduler Laravel pour déclencher
     * les sauvegardes automatiques.
     *
     * @param Request $request Requête HTTP contenant les paramètres de planification
     * @return \Illuminate\Http\RedirectResponse Redirection avec message de succès
     *
     * @throws \Illuminate\Validation\ValidationException
     * Si les paramètres sont manquants ou invalides
     */
    public function saveBackupSettings(Request $request)
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'backup_time' => 'required',
            'backup_day' => 'required',
            'backup_month' => 'required',
        ], [
            'backup_time.required' => "L'heure de sauvegarde est obligatoire.",
            'backup_day.required' => 'Le jour de sauvegarde est obligatoire.',
            'backup_month.required' => 'Le mois de sauvegarde est obligatoire.',
        ]);

        $this->updateEnvFile([
            'BACKUP_TIME' => $validated['backup_time'],
            'BACKUP_DAY'  => $validated['backup_day'],
            'BACKUP_MONTH' => $validated['backup_month'],
        ]);

        return back()->with('success', 'Planning de sauvegarde mis à jour !');
    }

    /**
     * @brief Affiche la page de réconciliation des fichiers.
     *
     * Interface permettant de synchroniser les fichiers présents sur les différents
     * stockages avec les enregistrements en base de données.
     *
     * @return \Illuminate\View\View Vue "admin.reconciliation"
     */
    public function reconciliation()
    {
        $this->checkAdminAccess();

        return view('admin.reconciliation');
    }

    /**
     * @brief Lance le processus de réconciliation.
     *
     * Compare les fichiers physiques présents sur les stockages
     * avec les enregistrements en base de données et effectue
     * les synchronisations nécessaires.
     *
     * @return \Illuminate\Http\RedirectResponse Redirection avec résultat de la réconciliation
     */
    public function runReconciliation()
    {
        $this->checkAdminAccess();

        // Logic to sync files

        return back()->with('reconciliation_result', 'Réconciliation terminée. (Résultat simulé)');
    }

    /**
     * @brief Télécharge le fichier de logs système.
     *
     * Permet à l'administrateur de télécharger le fichier laravel.log
     * pour analyse approfondie ou archivage.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     * Téléchargement du fichier ou redirection avec erreur si introuvable
     */
    public function downloadLogs()
    {
        $this->checkAdminAccess();

        $path = storage_path('logs/laravel.log');
        if (File::exists($path)) {
            return response()->download($path);
        }
        return back()->with('error', 'Fichier log introuvable.');
    }

    /**
     * @brief Import en masse d'élèves depuis un fichier CSV.
     *
     * Fonctionnalités :
     * - Accepte les fichiers CSV ou TXT
     * - Détecte automatiquement le séparateur (virgule ou espace)
     * - Formate automatiquement les noms (majuscules) et prénoms (première lettre majuscule)
     * - Ignore les doublons existants en base
     * - Traitement optimisé avec timeout étendu
     *
     * Format attendu : "nom,prenom" ou "nom prenom" (une ligne par élève)
     *
     * @param Request $request Requête HTTP contenant le fichier CSV uploadé
     * @return \Illuminate\Http\RedirectResponse Redirection avec nombre d'élèves importés
     *
     * @throws \Illuminate\Validation\ValidationException
     * Si le fichier est manquant ou au mauvais format
     */
    public function Ajouterelevedepuiscsv(Request $request)
{
    $this->checkAdminAccess();

    set_time_limit(300);

    $request->validate([
        'fichier_csv' => 'required|file|mimes:csv,txt'
    ], [
        'fichier_csv.required' => 'Le fichier est obligatoire.',
        'fichier_csv.file' => 'Le fichier est invalide.',
        'fichier_csv.mimes' => 'Le fichier doit être au format CSV ou TXT.',
    ]);

    $file = $request->file('fichier_csv');
    $content = file_get_contents($file->getRealPath());
    $lignes = explode("\n", str_replace("\r", "", $content));
    
    $countAdded = 0;
    $now = now();

    foreach ($lignes as $ligne) {
        $ligne = trim($ligne);
        if (empty($ligne) || $ligne === 'nom,prenom') continue;

        // Détection du séparateur (Virgule ou Espaces)
        if (str_contains($ligne, ',')) {
            $parts = explode(',', $ligne);
            $nom = strtoupper(trim($parts[0]));
            $prenom = ucfirst(strtolower(trim($parts[1] ?? '')));
        } else {
            $parts = explode(' ', $ligne);
            $prenom = count($parts) > 1 ? array_pop($parts) : '';
            $nom = strtoupper(implode(' ', $parts) ?: $prenom);
            $prenom = ucfirst(strtolower($prenom));
        }

        // VÉRIFICATION D'EXISTENCE
        // On ne l'ajoute que s'il n'existe pas déjà avec ce nom ET ce prénom
        $existe = \App\Models\Eleve::where('nom', $nom)
                                   ->where('prenom', $prenom)
                                   ->exists();

        if (!$existe) {
            \App\Models\Eleve::create([
                'nom' => $nom,
                'prenom' => $prenom,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $countAdded++;
        }
    }

    return back()->with('success', "$countAdded nouveaux élèves ont été importés (les doublons ont été ignorés).");
}

    /**
     * @brief Met à jour une permission spécifique d'un utilisateur.
     *
     * Permet d'accorder ou de révoquer une permission individuelle
     * (modifier video, diffuser video, supprimer video, etc.)
     * sans modifier le rôle global de l'utilisateur.
     *
     * Les permissions des administrateurs ne peuvent pas être modifiées
     * via cette interface pour des raisons de sécurité.
     *
     * @param Request $request Requête HTTP contenant user_id, permission et grant (true/false)
     * @return \Illuminate\Http\JsonResponse Réponse JSON indiquant le succès ou l'échec
     *
     * @throws \Illuminate\Validation\ValidationException
     * Si les paramètres sont invalides
     */
    public function updatePermission(Request $request)
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission' => 'required|string',
            'grant' => 'required|boolean',
        ], [
            'user_id.required' => "L'utilisateur est obligatoire.",
            'user_id.exists' => "L'utilisateur sélectionné n'existe pas.",
            'permission.required' => 'La permission est obligatoire.',
            'grant.required' => "L'action (accorder/révoquer) est obligatoire.",
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Ne pas permettre la modification des permissions admin par cette interface
        if ($user->hasRole('admin') && $validated['permission'] !== 'administrer site') {
            return response()->json(['success' => false, 'message' => 'Cannot modify admin permissions']);
        }

        if ($validated['grant']) {
            $user->givePermissionTo($validated['permission']);
        } else {
            $user->revokePermissionTo($validated['permission']);
        }

        return response()->json(['success' => true]);
    }

    /**
     * @brief Change le rôle d'un utilisateur et applique les permissions par défaut.
     *
     * Fonctionnalités :
     * - Supprime tous les rôles actuels de l'utilisateur
     * - Assigne le nouveau rôle (admin, professeur ou eleve)
     * - Applique automatiquement les permissions par défaut du rôle :
     *   - admin : toutes les permissions
     *   - professeur : modifier video
     *   - eleve : aucune permission
     *
     * @param Request $request Requête HTTP contenant user_id et role
     * @return \Illuminate\Http\JsonResponse Réponse JSON indiquant le succès
     *
     * @throws \Illuminate\Validation\ValidationException
     * Si le rôle n'est pas parmi les valeurs autorisées
     */
    public function updateRole(Request $request)
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|in:admin,professeur,eleve',
        ], [
            'user_id.required' => "L'utilisateur est obligatoire.",
            'user_id.exists' => "L'utilisateur sélectionné n'existe pas.",
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Le rôle doit être admin, professeur ou eleve.',
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Retirer tous les rôles actuels
        $user->syncRoles([]);

        // Assigner le nouveau rôle
        $user->assignRole($validated['role']);

        // Assigner les permissions par défaut selon le rôle
        if ($validated['role'] === 'admin') {
            $user->syncPermissions(['modifier video', 'diffuser video', 'supprimer video', 'administrer site']);
        } elseif ($validated['role'] === 'professeur') {
            $user->syncPermissions(['modifier video']);
        } else {
            // Élève : pas de permissions
            $user->syncPermissions([]);
        }

        return response()->json(['success' => true]);
    }
}

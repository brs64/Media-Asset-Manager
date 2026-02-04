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

    public function settings()
    {
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
     * HANDLE SETTINGS UPDATE
     */
    public function updateSettings(Request $request)
    {
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
     * HELPER: UPDATE .ENV FILE
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

    public function logs()
    {
        $logPath = storage_path('logs/laravel.log'); 
        $logs = [];

        if (File::exists($logPath)) {
            $file = file($logPath);
            $logs = array_slice($file, -50);
            if(config('btsplay.logs.recent_first')) {
                $logs = array_reverse($logs);
            }
        }

        return view('admin.logs', compact('logs'));
    }

    public function users()
    {
        // Only fetch users when on the Users tab
        $professeurs = Professeur::all();

        // Récupère les élèves avec le nombre de leurs participations pour la nouvelle table
    $eleves = Eleve::withCount('participations')->orderBy('nom')->orderBy('prenom')->get();

        return view('admin.users', compact('professeurs', 'eleves'));
    }

    /**
     * Créer un professeur
     */
    public function createProfesseur(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'identifiant' => 'required|string|unique:professeurs',
            'mot_de_passe' => 'required|min:8',
        ]);

        $validated['mot_de_passe'] = bcrypt($validated['mot_de_passe']);
        Professeur::create($validated);

        return back()->with('success', 'Professeur créé avec succès!');
    }

    /**
     * Supprimer un professeur
     */
    public function deleteProfesseur($id)
    {
        $professeur = Professeur::findOrFail($id);

        if ($professeur->media()->count() > 0) {
            return back()->withErrors('Impossible de supprimer un professeur référent de médias.');
        }

        $professeur->delete();
        return back()->with('success', 'Professeur supprimé avec succès!');
    }

    /**
     * Créer un élève
     */
    public function createEleve(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
        ]);

        Eleve::create($validated);
        return back()->with('success', 'Élève créé avec succès!');
    }

    /**
     * Supprimer un élève
     */
    public function deleteEleve($id)
    {
        $eleve = Eleve::findOrFail($id);
        $eleve->delete();
        return back()->with('success', 'Élève supprimé avec succès!');
    }

    /**
     * Gestion des projets
     */
    public function projets()
    {
        $projets = \App\Models\Projet::withCount('media')->paginate(20);
        return view('admin.projets', compact('projets'));
    }

    /**
     * Créer un projet
     */
    public function createProjet(Request $request)
    {
        $validated = $request->validate([
            'libelle' => 'required|string|max:255',
        ]);

        \App\Models\Projet::create($validated);

        return back()->with('success', 'Projet créé avec succès!');
    }

    /**
     * Supprimer un projet
     */
    public function deleteProjet($id)
    {
        $projet = \App\Models\Projet::findOrFail($id);

        if ($projet->media()->count() > 0) {
            return back()->withErrors('Impossible de supprimer un projet contenant des médias.');
        }

        $projet->delete();

        return back()->with('success', 'Projet supprimé avec succès!');
    }

    public function databaseView() {
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

    public function runBackup() {
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
    
    public function saveBackupSettings(Request $request) {
        $validated = $request->validate([
            'backup_time' => 'required',
            'backup_day' => 'required',
            'backup_month' => 'required',
        ]);

        $this->updateEnvFile([
            'BACKUP_TIME' => $validated['backup_time'],
            'BACKUP_DAY'  => $validated['backup_day'],
            'BACKUP_MONTH' => $validated['backup_month'],
        ]);

        return back()->with('success', 'Planning de sauvegarde mis à jour !');
    }

    public function reconciliation(){
        return view('admin.reconciliation');
    }
    
    public function runReconciliation() {
        // Logic to sync files
        
        return back()->with('reconciliation_result', 'Réconciliation terminée. (Résultat simulé)');
    }
    
    public function downloadLogs() {
        $path = storage_path('logs/laravel.log');
        if (File::exists($path)) {
            return response()->download($path);
        }
        return back()->with('error', 'Fichier log introuvable.');
    }
}

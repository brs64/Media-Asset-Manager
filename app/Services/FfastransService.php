<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * @brief Service de communication avec l'API FFAStrans.
 *
 * FFAStrans est un système de workflow de transcodage vidéo professionnel.
 * Ce service permet de soumettre des jobs de transcodage et de suivre leur progression.
 *
 * Fonctionnalités principales :
 * - Soumission de jobs de transcodage vers des workflows FFAStrans
 * - Suivi de l'état des jobs actifs et historique
 * - Traduction automatique des chemins Linux vers Windows (UNC)
 * - Annulation de jobs en cours
 * - Authentification HTTP Basic Auth
 *
 * Configuration requise (config/services.php) :
 * - ffastrans.url : URL de base de l'API FFAStrans
 * - ffastrans.user : Nom d'utilisateur (optionnel)
 * - ffastrans.password : Mot de passe (optionnel)
 * - ffastrans.path_local : Racine locale (ex: /mnt/archivage)
 * - ffastrans.path_remote : Racine distante UNC (ex: \\\\NAS\\archivage)
 *
 * API FFAStrans v2 : https://docs.ffastrans.com/api/v2/
 */
class FfastransService
{
    /** @brief URL de base de l'API FFAStrans */
    protected string $baseUrl;

    /** @brief Nom d'utilisateur pour authentification */
    protected ?string $username;

    /** @brief Mot de passe pour authentification */
    protected ?string $password;

    /**
     * @brief Initialise le service avec les paramètres de connexion FFAStrans.
     *
     * Charge la configuration depuis config/services.php
     */
    public function __construct()
    {
        $this->baseUrl = config('services.ffastrans.url');
        $this->username = config('services.ffastrans.user');
        $this->password = config('services.ffastrans.password');
    }

    /**
     * @brief Crée un client HTTP configuré pour FFAStrans.
     *
     * Configuration :
     * - Timeout connexion : 3 secondes
     * - Timeout requête : 10 secondes
     * - Accept: application/json
     * - Authentification Basic Auth si credentials fournis
     *
     * @return \Illuminate\Http\Client\PendingRequest Client HTTP configuré
     */
    protected function client()
    {
        $client = Http::connectTimeout(3)->timeout(10)->acceptJson();
        if ($this->username && $this->password) {
            $client->withBasicAuth($this->username, $this->password);
        }
        return $client;
    }

    /**
     * @brief Soumet un job de transcodage à FFAStrans.
     *
     * Processus :
     * 1. Traduit le chemin source Linux vers Windows UNC si nécessaire
     * 2. Envoie la requête à l'API FFAStrans
     * 3. Retourne l'ID du job créé
     *
     * Les chemins commençant par \\\\ sont considérés comme déjà au format UNC
     * et ne sont pas modifiés.
     *
     * @param string $sourceFile Chemin du fichier source (Linux ou UNC)
     * @param string $workflowId Identifiant du workflow FFAStrans à utiliser
     * @param array $variables Variables personnalisées à passer au workflow (optionnel)
     * @return array Réponse JSON de l'API contenant le job_id
     *
     * @throws Exception Si la soumission échoue
     */
    public function submitJob(string $sourceFile, string $workflowId, array $variables = [])
    {
        if (str_starts_with($sourceFile, '\\\\')) {
            $finalPath = $sourceFile;
        } else {
            $finalPath = $this->translatePath($sourceFile);
        };

        $endpoint = "{$this->baseUrl}/api/json/v2/jobs";
        
        $payload = [
            'wf_id' => $workflowId,
            'inputfile' => $finalPath,
            'priority' => 5,
            'variables' => $variables
        ];

        try {
            $response = $this->client()->post($endpoint, $payload);
            
            if ($response->failed()) {
                 Log::error('FFAStrans Submit Error', ['body' => $response->body()]);
                 throw new Exception("Failed to submit job: " . $response->status());
            }
            return $response->json();
            
        } catch (Exception $e) {
            Log::error('FFAStrans Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @brief Traduit un chemin Linux vers un chemin Windows UNC.
     *
     * Cette méthode permet de convertir les chemins du serveur Linux
     * vers des chemins accessibles par FFAStrans sur Windows.
     *
     * Exemples :
     * - /mnt/archivage/video.mp4 → \\\\NAS\\archivage\\video.mp4
     * - video/test.mp4 → \\\\NAS\\video\\test.mp4
     *
     * Stratégies de traduction :
     * 1. Si le chemin commence par path_local, remplace par path_remote
     * 2. Si le chemin est relatif, préfixe avec path_remote
     * 3. Convertit les slashes / en backslashes \\
     *
     * @param string $linuxPath Chemin Linux à traduire
     * @return string Chemin Windows UNC
     */
    public function translatePath(string $linuxPath): string
    {
        $localRoot  = config('services.ffastrans.path_local');
        $remoteRoot = config('services.ffastrans.path_remote');
        $finalPath = $linuxPath;

        if ($remoteRoot) {
            $remoteRoot = rtrim($remoteRoot, '\\/');
            if ($localRoot && str_starts_with($linuxPath, $localRoot)) {
                $relativePath = substr($linuxPath, strlen($localRoot));
                $relativePath = ltrim($relativePath, '/\\');
                $finalPath = $remoteRoot . DIRECTORY_SEPARATOR . $relativePath;
            }
            elseif (!str_starts_with($linuxPath, '/') && !preg_match('/^[a-zA-Z]:/', $linuxPath)) {
                $finalPath = $remoteRoot . DIRECTORY_SEPARATOR . $linuxPath;
            }
        }
        return str_replace('/', '\\', $finalPath);
    }

    /**
     * @brief Traduit les statuts anglais FFAStrans vers le français.
     *
     * Mapping des statuts :
     * - success/finished/done → Terminé
     * - error/failed/aborted → Echoué
     * - cancelled/canceled → Annulé
     * - vide ou autre → En cours
     *
     * @param string|null $status Statut anglais de FFAStrans
     * @return string Statut traduit en français
     */
    private function translateStatus($status)
    {
        $s = strtolower($status ?? '');
        if (in_array($s, ['success', 'finished', 'done'])) return 'Terminé';
        if (in_array($s, ['error', 'failed', 'aborted'])) return 'Echoué';
        if (in_array($s, ['cancelled', 'canceled'])) return 'Annulé';
        if (empty($s)) return 'En cours';
        return 'En cours'; // Default fall back
    }

    /**
     * @brief Récupère la liste complète des jobs (actifs + historique).
     *
     * Cette méthode fusionne deux sources :
     * 1. Jobs actifs via /api/json/v2/jobs
     * 2. Historique (50 derniers) via /api/json/v2/history
     *
     * Les jobs sont triés par date décroissante (plus récents en premier).
     *
     * Structure de retour pour chaque job :
     * - id : identifiant du job
     * - filename : nom du fichier source
     * - status : statut traduit en français
     * - progress : progression de 0 à 100
     * - date : horodatage
     * - is_finished : boolean indiquant si le job est terminé
     *
     * @return array Liste des jobs triés par date décroissante
     */
    public function getFullStatusList()
    {
        // Get Active Jobs
        $activeResponse = $this->client()->get("{$this->baseUrl}/api/json/v2/jobs");
        $activeJobs = $activeResponse->successful() ? ($activeResponse->json()['jobs'] ?? []) : [];

        // Get History (Last 50)
        $historyResponse = $this->client()->get("{$this->baseUrl}/api/json/v2/history?start=0&count=50");
        $historyJobs = $historyResponse->successful() ? ($historyResponse->json()['history'] ?? []) : [];

        $allJobs = [];

        // Map Active
        foreach ($activeJobs as $job) {
            $allJobs[] = [
                'id' => $job['job_id'] ?? $job['guid'],
                'filename' => basename($job['input'] ?? 'Fichier Inconnu'),
                'status' => 'En cours', // Active is always "En cours"
                'progress' => $job['progress'] ?? 0,
                'date' => $job['submit_time'] ?? date('Y-m-d H:i:s'),
                'is_finished' => false
            ];
        }

        // Map History
        foreach ($historyJobs as $job) {
            $rawResult = $job['result'] ?? '';
            $frenchStatus = $this->translateStatus($rawResult);

            $allJobs[] = [
                'id' => $job['job_id'] ?? $job['guid'],
                'filename' => basename($job['source'] ?? 'Fichier Inconnu'),
                'status' => $frenchStatus,
                'progress' => 100,
                'date' => $job['end_time'] ?? date('Y-m-d H:i:s'),
                'is_finished' => true
            ];
        }

        usort($allJobs, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $allJobs;
    }

    /**
     * @brief Récupère le statut détaillé d'un job spécifique.
     *
     * Recherche d'abord dans les jobs actifs, puis dans l'historique si introuvable.
     *
     * Informations retournées selon la source :
     * - Active : source='active', progress, state, status, steps, proc
     * - History : source='history', progress=100, state, message
     * - Non trouvé : source='not_found', state='Error'
     *
     * @param string $jobId Identifiant unique du job FFAStrans
     * @return array Informations détaillées du job
     */
    public function getJobStatus(string $jobId)
    {
        $endpoint = "{$this->baseUrl}/api/json/v2/jobs/{$jobId}";
        $response = $this->client()->get($endpoint);

        if ($response->successful()) {
            $data = $response->json();
            
            if (!empty($data['jobs'])) {
                $job = $data['jobs'][0];
                return [
                    'source'   => 'active',
                    'progress' => $job['progress'] ?? 0,
                    'state'    => $job['state'] ?? 'Running',
                    'status'   => $job['status'] ?? '',
                    'steps'    => $job['steps'] ?? '',
                    'proc'     => $job['proc'] ?? ''
                ];
            }
        }

        $historyEndpoint = "{$this->baseUrl}/api/json/v2/history/{$jobId}";
        $historyResponse = $this->client()->get($historyEndpoint);

        if ($historyResponse->successful()) {
            $historyData = $historyResponse->json();
            return [
                'source'   => 'history',
                'state'    => $historyData['result'] ?? 'Success',
                'progress' => 100,
                'message'  => $historyData['msg'] ?? ''
            ];
        }

        return ['source' => 'not_found', 'state' => 'Error'];
    }

    /**
     * @brief Annule un job en cours d'exécution.
     *
     * Envoie une requête DELETE à l'API FFAStrans pour stopper le job.
     * Ne fonctionne que sur les jobs actifs (pas l'historique).
     *
     * @param string $jobId Identifiant du job à annuler
     * @return bool true si l'annulation a réussi, false sinon
     */
    public function cancelJob(string $jobId)
    {
        $endpoint = "{$this->baseUrl}/api/json/v2/jobs/{$jobId}";
        $response = $this->client()->delete($endpoint);
        return $response->successful();
    }
}
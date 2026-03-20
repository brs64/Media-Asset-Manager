<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Professeur;
use App\Models\Eleve;
use App\Models\Projet;
use App\Models\Media;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'professeur']);
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
    }

    /**
     * @test
     * GIVEN : un administrateur authentifié
     * WHEN : il accède à la page des réglages
     * THEN : la page s'affiche avec les paramètres de configuration
     */
    public function settings_page_displays_configuration()
    {
        $response = $this->actingAs($this->user)->get(route('admin.settings'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.settings');
        $response->assertViewHas('settings');
    }

    /**
     * @test
     * GIVEN : un fichier de log existant avec des entrées
     * WHEN : l'administrateur accède à la page des logs
     * THEN : la page s'affiche avec les logs récents
     */
    public function logs_page_displays_recent_logs()
    {
        // Create a test log file
        $logPath = storage_path('logs/laravel.log');
        File::ensureDirectoryExists(dirname($logPath));
        File::put($logPath, "[2024-01-01 10:00:00] Test log entry\n[2024-01-01 11:00:00] Another entry\n");

        $response = $this->actingAs($this->user)->get(route('admin.logs'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.logs');
        $response->assertViewHas('logs');

        // Clean up
        File::delete($logPath);
    }

    /**
     * @test
     * GIVEN : aucun fichier de log n'existe
     * WHEN : l'administrateur accède à la page des logs
     * THEN : la page s'affiche avec une liste de logs vide
     */
    public function logs_page_handles_missing_log_file()
    {
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            File::delete($logPath);
        }

        $response = $this->actingAs($this->user)->get(route('admin.logs'));

        $response->assertStatus(200);
        $response->assertViewHas('logs', []);
    }

    /**
     * @test
     * GIVEN : des professeurs et des élèves existent en base
     * WHEN : l'administrateur accède à la page des utilisateurs
     * THEN : la page affiche les listes de professeurs et d'élèves
     */
    public function users_page_displays_professeurs_and_eleves()
    {
        Professeur::factory()->count(3)->create();
        Eleve::factory()->count(5)->create();

        $response = $this->actingAs($this->user)->get(route('admin.users'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users');
        $response->assertViewHas('professeurs');
        $response->assertViewHas('eleves');
    }

    /**
     * @test
     * GIVEN : un administrateur authentifié avec des données valides
     * WHEN : il soumet le formulaire de création d'un professeur
     * THEN : le professeur et son utilisateur sont créés en base
     */
    public function createProfesseur_creates_new_professor()
    {
        $response = $this->actingAs($this->user)->post(route('admin.professeurs.create'), [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'identifiant' => 'jdupont',
            'mot_de_passe' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['name' => 'jdupont']);
        $this->assertDatabaseHas('professeurs', [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
        ]);
    }

    /**
     * @test
     * GIVEN : un administrateur authentifié
     * WHEN : il soumet le formulaire de création sans données
     * THEN : des erreurs de validation sont retournées pour les champs obligatoires
     */
    public function createProfesseur_validates_required_fields()
    {
        $response = $this->actingAs($this->user)->post(route('admin.professeurs.create'), []);

        $response->assertSessionHasErrors(['nom', 'prenom', 'identifiant', 'mot_de_passe']);
    }

    /**
     * @test
     * GIVEN : un utilisateur avec l'identifiant 'jdupont' existe déjà
     * WHEN : on tente de créer un professeur avec le même identifiant
     * THEN : une erreur de validation est retournée pour l'identifiant
     */
    public function createProfesseur_validates_unique_identifiant()
    {
        User::factory()->create(['name' => 'jdupont']);

        $response = $this->actingAs($this->user)->post(route('admin.professeurs.create'), [
            'nom' => 'Autre',
            'prenom' => 'Nom',
            'identifiant' => 'jdupont',
            'mot_de_passe' => 'password123',
        ]);

        $response->assertSessionHasErrors('identifiant');
    }

    /**
     * @test
     * GIVEN : un professeur sans médias associés
     * WHEN : l'administrateur supprime ce professeur
     * THEN : le professeur est supprimé de la base
     */
    public function deleteProfesseur_deletes_professor_without_media()
    {
        $prof = Professeur::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('admin.professeurs.delete', $prof->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('professeurs', ['id' => $prof->id]);
    }

    /**
     * @test
     * GIVEN : un professeur ayant des médias associés
     * WHEN : l'administrateur tente de le supprimer
     * THEN : la suppression est refusée et le professeur reste en base
     */
    public function deleteProfesseur_prevents_deletion_if_has_media()
    {
        $user = User::factory()->create();
        $prof = Professeur::factory()->create(['user_id' => $user->id]);
        Media::factory()->create(['professeur_id' => $prof->id]);

        $response = $this->actingAs($this->user)->delete(route('admin.professeurs.delete', $prof->id));

        $response->assertRedirect();
        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('professeurs', ['id' => $prof->id]);
    }

    /**
     * @test
     * GIVEN : un administrateur authentifié avec des données valides
     * WHEN : il soumet le formulaire de création d'un élève
     * THEN : l'élève est créé en base avec les bonnes informations
     */
    public function createEleve_creates_new_student()
    {
        $response = $this->actingAs($this->user)->post(route('admin.eleves.create'), [
            'nom' => 'Martin',
            'prenom' => 'Sophie',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('eleves', [
            'nom' => 'Martin',
            'prenom' => 'Sophie',
        ]);
    }

    /**
     * @test
     * GIVEN : un administrateur authentifié
     * WHEN : il soumet le formulaire de création d'un élève sans données
     * THEN : des erreurs de validation sont retournées pour nom et prénom
     */
    public function createEleve_validates_required_fields()
    {
        $response = $this->actingAs($this->user)->post(route('admin.eleves.create'), []);

        $response->assertSessionHasErrors(['nom', 'prenom']);
    }

    /**
     * @test
     * GIVEN : un élève existant en base
     * WHEN : l'administrateur supprime cet élève
     * THEN : l'élève est supprimé de la base
     */
    public function deleteEleve_deletes_student()
    {
        $eleve = Eleve::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('admin.eleves.delete', $eleve->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('eleves', ['id' => $eleve->id]);
    }

    // NOTE: Project management routes don't exist in routes/web.php
    // Tests removed as these features are not implemented

    /**
     * @test
     * GIVEN : un fichier de log de sauvegarde existant
     * WHEN : l'administrateur accède à la page de base de données
     * THEN : la page affiche les logs de sauvegarde
     */
    public function databaseView_displays_backup_logs()
    {
        $logPath = storage_path('logs/backup.log');
        File::ensureDirectoryExists(dirname($logPath));
        File::put($logPath, "[2024-01-01] Backup successful\n");

        $response = $this->actingAs($this->user)->get(route('admin.database'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.database');
        $response->assertViewHas('backupLogs');

        File::delete($logPath);
    }

    /**
     * @test
     * GIVEN : un administrateur authentifié et la commande de sauvegarde disponible
     * WHEN : il lance une sauvegarde manuelle
     * THEN : la commande s'exécute avec succès et un message de confirmation s'affiche
     */
    public function runBackup_executes_backup_command_successfully()
    {
        // Mock Artisan call
        Artisan::shouldReceive('call')
            ->once()
            ->with('db:backup --type=manual')
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Backup successful');

        $response = $this->actingAs($this->user)->post(route('admin.backup.run'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /**
     * @test
     * GIVEN : un fichier de log existant avec du contenu
     * WHEN : l'administrateur demande le téléchargement des logs
     * THEN : le fichier est téléchargé avec succès
     */
    public function downloadLogs_downloads_log_file()
    {
        $logPath = storage_path('logs/laravel.log');
        File::ensureDirectoryExists(dirname($logPath));
        File::put($logPath, "Test log content");

        $response = $this->actingAs($this->user)->get(route('admin.logs.download'));

        $response->assertStatus(200);
        $response->assertDownload('laravel.log');

        File::delete($logPath);
    }

    /**
     * @test
     * GIVEN : aucun fichier de log n'existe
     * WHEN : l'administrateur demande le téléchargement des logs
     * THEN : une erreur est retournée avec redirection
     */
    public function downloadLogs_returns_error_when_file_missing()
    {
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            File::delete($logPath);
        }

        $response = $this->actingAs($this->user)->get(route('admin.logs.download'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * @test
     * GIVEN : un fichier CSV contenant trois élèves
     * WHEN : l'administrateur importe le fichier CSV
     * THEN : les trois élèves sont créés en base avec les noms en majuscules
     */
    public function Ajouterelevedepuiscsv_imports_students_from_csv()
    {
        $csvContent = "nom,prenom\nDupont,Jean\nMartin,Sophie\nDurand,Pierre";
        $file = UploadedFile::fake()->createWithContent('eleves.csv', $csvContent);

        $response = $this->actingAs($this->user)->post(route('admin.eleves.bulk'), [
            'fichier_csv' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('eleves', ['nom' => 'DUPONT', 'prenom' => 'Jean']);
        $this->assertDatabaseHas('eleves', ['nom' => 'MARTIN', 'prenom' => 'Sophie']);
        $this->assertDatabaseHas('eleves', ['nom' => 'DURAND', 'prenom' => 'Pierre']);
    }

    /**
     * @test
     * GIVEN : un fichier texte avec des noms séparés par des espaces
     * WHEN : l'administrateur importe le fichier
     * THEN : les élèves sont créés correctement malgré le format différent
     */
    public function Ajouterelevedepuiscsv_handles_space_separated_format()
    {
        $csvContent = "Dupont Jean\nMartin Sophie";
        $file = UploadedFile::fake()->createWithContent('eleves.txt', $csvContent);

        $response = $this->actingAs($this->user)->post(route('admin.eleves.bulk'), [
            'fichier_csv' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('eleves', ['nom' => 'DUPONT', 'prenom' => 'Jean']);
        $this->assertDatabaseHas('eleves', ['nom' => 'MARTIN', 'prenom' => 'Sophie']);
    }

    /**
     * @test
     * GIVEN : un élève 'DUPONT Jean' existe déjà en base
     * WHEN : on importe un CSV contenant ce même élève et un nouveau
     * THEN : seul le nouvel élève est ajouté, le doublon est ignoré
     */
    public function Ajouterelevedepuiscsv_ignores_duplicates()
    {
        Eleve::factory()->create(['nom' => 'DUPONT', 'prenom' => 'Jean']);

        $csvContent = "nom,prenom\nDupont,Jean\nMartin,Sophie";
        $file = UploadedFile::fake()->createWithContent('eleves.csv', $csvContent);

        $response = $this->actingAs($this->user)->post(route('admin.eleves.bulk'), [
            'fichier_csv' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Only Martin Sophie should be added (not Dupont Jean)
        $this->assertEquals(1, Eleve::where('nom', 'DUPONT')->where('prenom', 'Jean')->count());
        $this->assertDatabaseHas('eleves', ['nom' => 'MARTIN', 'prenom' => 'Sophie']);
    }

    /**
     * @test
     * GIVEN : un fichier PDF (type non autorisé)
     * WHEN : l'administrateur tente de l'importer comme fichier d'élèves
     * THEN : une erreur de validation est retournée pour le type de fichier
     */
    public function Ajouterelevedepuiscsv_validates_file_type()
    {
        $file = UploadedFile::fake()->create('eleves.pdf', 100);

        $response = $this->actingAs($this->user)->post(route('admin.eleves.bulk'), [
            'fichier_csv' => $file,
        ]);

        $response->assertSessionHasErrors('fichier_csv');
    }

    /**
     * @test
     * GIVEN : un fichier CSV contenant des lignes vides intercalées
     * WHEN : l'administrateur importe le fichier
     * THEN : les lignes vides sont ignorées et les élèves sont créés correctement
     */
    public function Ajouterelevedepuiscsv_handles_empty_lines()
    {
        $csvContent = "nom,prenom\n\nDupont,Jean\n\n\nMartin,Sophie\n";
        $file = UploadedFile::fake()->createWithContent('eleves.csv', $csvContent);

        $response = $this->actingAs($this->user)->post(route('admin.eleves.bulk'), [
            'fichier_csv' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('eleves', ['nom' => 'DUPONT', 'prenom' => 'Jean']);
        $this->assertDatabaseHas('eleves', ['nom' => 'MARTIN', 'prenom' => 'Sophie']);
    }

    /**
     * @test
     * GIVEN : un administrateur authentifié
     * WHEN : il accède à la page de réconciliation
     * THEN : la page s'affiche correctement
     */
    public function reconciliation_page_displays()
    {
        $response = $this->actingAs($this->user)->get(route('admin.reconciliation'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.reconciliation');
    }

    /**
     * @test
     * GIVEN : un administrateur authentifié
     * WHEN : il lance la réconciliation
     * THEN : la synchronisation s'exécute et le résultat est stocké en session
     */
    public function runReconciliation_executes_sync()
    {
        $response = $this->actingAs($this->user)->post(route('admin.reconciliation.run'));

        $response->assertRedirect();
        $response->assertSessionHas('reconciliation_result');
    }

    /**
     * @test
     * GIVEN : un administrateur avec des paramètres de sauvegarde valides
     * WHEN : il enregistre la configuration de sauvegarde
     * THEN : les paramètres sont mis à jour avec succès
     */
    public function saveBackupSettings_updates_backup_configuration()
    {
        $response = $this->actingAs($this->user)->post(route('admin.backup.save'), [
            'backup_time' => '02:00',
            'backup_day' => 'Monday',
            'backup_month' => '*',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /**
     * @test
     * GIVEN : un administrateur authentifié
     * WHEN : il soumet le formulaire de sauvegarde sans données
     * THEN : des erreurs de validation sont retournées pour les champs obligatoires
     */
    public function saveBackupSettings_validates_required_fields()
    {
        $response = $this->actingAs($this->user)->post(route('admin.backup.save'), []);

        $response->assertSessionHasErrors(['backup_time', 'backup_day', 'backup_month']);
    }
}

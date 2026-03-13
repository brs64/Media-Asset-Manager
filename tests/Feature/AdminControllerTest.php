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
        $this->user = User::factory()->create();
    }

    /** @test */
    public function settings_page_displays_configuration()
    {
        $response = $this->actingAs($this->user)->get(route('admin.settings'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.settings');
        $response->assertViewHas('settings');
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
    public function createProfesseur_creates_new_professor()
    {
        $userData = User::factory()->create();

        $response = $this->actingAs($this->user)->post(route('admin.professeurs.create'), [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'identifiant' => 'jdupont',
            'mot_de_passe' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('professeurs', [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'identifiant' => 'jdupont',
        ]);
    }

    /** @test */
    public function createProfesseur_validates_required_fields()
    {
        $response = $this->actingAs($this->user)->post(route('admin.professeurs.create'), []);

        $response->assertSessionHasErrors(['nom', 'prenom', 'identifiant', 'mot_de_passe']);
    }

    /** @test */
    public function createProfesseur_validates_unique_identifiant()
    {
        $prof = Professeur::factory()->create(['identifiant' => 'jdupont']);

        $response = $this->actingAs($this->user)->post(route('admin.professeurs.create'), [
            'nom' => 'Autre',
            'prenom' => 'Nom',
            'identifiant' => 'jdupont',
            'mot_de_passe' => 'password123',
        ]);

        $response->assertSessionHasErrors('identifiant');
    }

    /** @test */
    public function deleteProfesseur_deletes_professor_without_media()
    {
        $prof = Professeur::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('admin.professeurs.delete', $prof->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('professeurs', ['id' => $prof->id]);
    }

    /** @test */
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

    /** @test */
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

    /** @test */
    public function createEleve_validates_required_fields()
    {
        $response = $this->actingAs($this->user)->post(route('admin.eleves.create'), []);

        $response->assertSessionHasErrors(['nom', 'prenom']);
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
    public function Ajouterelevedepuiscsv_validates_file_type()
    {
        $file = UploadedFile::fake()->create('eleves.pdf', 100);

        $response = $this->actingAs($this->user)->post(route('admin.eleves.bulk'), [
            'fichier_csv' => $file,
        ]);

        $response->assertSessionHasErrors('fichier_csv');
    }

    /** @test */
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

    /** @test */
    public function reconciliation_page_displays()
    {
        $response = $this->actingAs($this->user)->get(route('admin.reconciliation'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.reconciliation');
    }

    /** @test */
    public function runReconciliation_executes_sync()
    {
        $response = $this->actingAs($this->user)->post(route('admin.reconciliation.run'));

        $response->assertRedirect();
        $response->assertSessionHas('reconciliation_result');
    }

    /** @test */
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

    /** @test */
    public function saveBackupSettings_validates_required_fields()
    {
        $response = $this->actingAs($this->user)->post(route('admin.backup.save'), []);

        $response->assertSessionHasErrors(['backup_time', 'backup_day', 'backup_month']);
    }
}

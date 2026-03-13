namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminEleveTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function un_admin_peut_importer_des_eleves_via_un_fichier()
    {
        // 1. ARRANGE : On crée un admin et on simule un fichier CSV
        $admin = User::factory()->create(); // À adapter selon ton système de rôles
        
        $content = "nom,prenom\nDUBOIS,Jean\nMAURIN,Alice";
        $file = UploadedFile::fake()->createWithContent('eleves.csv', $content);

        // 2. ACT : On envoie le fichier à la route correspondante (nommée dans ton web.php)
        $response = $this->actingAs($admin)
                         ->post(route('eleves.bulk'), [
                             'file' => $file
                         ]);

        // 3. ASSERT : On vérifie la redirection et la présence en base
        $response->assertStatus(302);
        $this->assertDatabaseHas('eleves', ['nom' => 'DUBOIS', 'prenom' => 'Jean']);
        $this->assertDatabaseHas('eleves', ['nom' => 'MAURIN', 'prenom' => 'Alice']);
    }
}
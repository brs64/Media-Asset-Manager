<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class Professeur extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Illuminate\Support\Facades\DB::table('Professeur')->insert([
            ['identifiant' => 'amakdessi',      'nom' => 'MAKDESSI',           'prenom' => 'Aurélia',       'motdepasse' => 'ca665281ccbbf4c28959d40437617a681d6d6f7088e71997ba6fbfc9e887d09b', 'role' => 'Professeur'],
            ['identifiant' => 'blagoarde',      'nom' => 'LAGOARDE',           'prenom' => 'Beñat',         'motdepasse' => '80a52be04dcfde4543a339650213e8b28be32d4e99ccef9fb1a87cf192e1ff5f', 'role' => 'Professeur'],
            ['identifiant' => 'claplace',       'nom' => 'LAPLACE',            'prenom' => 'Christophe',    'motdepasse' => '81e234882ad395d8452fc392423af728f3d2a757e4ed46f1379fa8ce8b69562f', 'role' => 'Professeur'],
            ['identifiant' => 'flafittehoussat','nom' => 'LAFITTE-HOUSSAT',    'prenom' => 'François',      'motdepasse' => '5000575b3421b9d731484fdbbdc42819fe851d36df7fc421ef3f5f236a6bbc85', 'role' => 'Professeur'],
            ['identifiant' => 'gberthome',      'nom' => 'BERTHOME',           'prenom' => 'Gilles',        'motdepasse' => '55184f0f32987091611a6356d91377fc071762c84928a89476a088e1fb813af3', 'role' => 'Professeur'],
            ['identifiant' => 'jlmathieu',      'nom' => 'MATHIEU',            'prenom' => 'Jean-Luc',      'motdepasse' => '6f0a4134ceed36a892514fffd50b01704dc703e75c5ba3ea160a773382a3300c', 'role' => 'Professeur'],
            ['identifiant' => 'jmjeault',       'nom' => 'JEAULT',             'prenom' => 'Jean-Michel',   'motdepasse' => 'ff2f99f2c0825a9d5795bfd6dfc34985e779c0c4f0379ece35191424132eadcc', 'role' => 'Professeur'],
            ['identifiant' => 'jmlamagnere',    'nom' => 'LAMAGNERE',          'prenom' => 'Jean-Michel',   'motdepasse' => '94f89a938f13131c33c3e51106f81463bfa5c7fcad5bf911ab8c76fd7bc39f82', 'role' => 'Professeur'],
            ['identifiant' => 'jrlafourcade',   'nom' => 'LAFOURCADE',         'prenom' => 'Jean-Robert',   'motdepasse' => 'f9ba07f33a2f948cfcdd4fd88566116c8d459868b73fefc94e33e66d9a3328a6', 'role' => 'Professeur'],
            ['identifiant' => 'llagoardesegot', 'nom' => 'LAGOARDE SEGOT',     'prenom' => 'Lison',         'motdepasse' => 'aaff1c6c3a015682d1f312bebcd6e8287ef03d1fbf83b783ba431beb03d1c9a7', 'role' => 'Professeur'],
            ['identifiant' => 'lmariesainte',   'nom' => 'MARIE SAINTE',       'prenom' => 'Luc',           'motdepasse' => '0273b8998352c94f3c52a9fb95b0e86aff8ee28f0b966ceb9e89152261d03ee0', 'role' => 'Professeur'],
            ['identifiant' => 'nconguisti',     'nom' => 'CONGUISTI',          'prenom' => 'Nicolas',       'motdepasse' => '4c5cddb7859b93eebf26c551518c021a31fa0013b2c03afa5b541cbc8bd079a6', 'role' => 'Administrateur'],
            ['identifiant' => 'plucu',          'nom' => 'LUCU',               'prenom' => 'Pascal',        'motdepasse' => 'bb9bd039139a013c3b32923a80c0b7281c7de4dee780e011067c8cd4c3a01c1a', 'role' => 'Professeur'],
            ['identifiant' => 'pverdier',       'nom' => 'VERDIER',            'prenom' => 'Pascal',        'motdepasse' => '3ba03ab1d3dd55b1d8fd836a71b916d560fba8fa595ede55b47c34a4cba72dfe', 'role' => 'Professeur'],
            ['identifiant' => 'schareyron',     'nom' => 'CHAREYRON',          'prenom' => 'Sophie',        'motdepasse' => '1ee641b706e1da6c74b0b61938fde85318b64641b0192332bb8a2eb17e09a4a4', 'role' => 'Professeur'],
            ['identifiant' => 'slescoulier',    'nom' => 'LESCOULIER',         'prenom' => 'Serge',         'motdepasse' => '4400c8bae84ecc59b13ccb5ff8bd2fad3e4b2660ac4a9b5b22e08f939da0ea22', 'role' => 'Professeur'],
        ]);
    }
}

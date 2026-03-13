<?php

namespace Database\Factories;

use App\Models\Eleve;
use Illuminate\Database\Eloquent\Factories\Factory;

//CR2ATION DE FAUX ELEVES POUR LES TEST SUR LES METHODES ADMIN 
class EleveFactory extends Factory
{
//LIER FACTORY AU MODEL ELEVE
    protected $model = Eleve::class;
     
    public function definition(): array
    {
        return [
            //CREER UN FAUX NOM EN MAJUSCULE
            'nom' => strtoupper($this->faker->lastName()),

           //GENERE UN PRENOM AVEC LA RPEMIERE LETTRE DU PRENOM EN MAJUSCULE
            'prenom' => ucfirst(strtolower($this->faker->firstName())),
            
            //AJOUT DE DATE DE CREATION AUTO
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
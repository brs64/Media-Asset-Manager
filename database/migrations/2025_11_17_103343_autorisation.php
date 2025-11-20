<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('Autorisation')) {
            Schema::create('Autorisation', function (Blueprint $table) {
                $table->string('professeur', 100);
                $table->boolean('modifier')->default(0);
                $table->boolean('supprimer')->default(0);
                $table->boolean('diffuser')->default(0);
                $table->boolean('administrer')->default(0);
                $table->primary('professeur');
                
               $table->foreign('professeur', 'fk_professeur_autorisation')
                      ->references('identifiant')->on('Professeur')
                      ->onDelete('cascade');
            
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

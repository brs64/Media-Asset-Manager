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
        Schema::dropIfExists('Professeur');

        Schema::create('Professeur', function (Blueprint $table) {
            $table->string('identifiant', 100);
            $table->string('nom', 50);
            $table->string('prenom', 50);
            $table->string('motdepasse', 255);
            $table->string('role', 15)->nullable();
            $table->primary('identifiant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

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
        Schema::dropIfExists('Participer');

        Schema::create('Participer', function (Blueprint $table) {
            // Match types of referenced PKs:
            // Etudiant uses $table->id() -> unsignedBigInteger
            $table->unsignedBigInteger('idEtudiant');
            // Media and Role use increments() -> unsignedInteger
            $table->unsignedInteger('idMedia');
            $table->unsignedInteger('idRole');

            $table->primary(['idMedia', 'idEtudiant', 'idRole']);

            // indexes (optional names)
            $table->index('idEtudiant', 'fk_Etudiant');
            $table->index('idRole', 'fk_role');
            $table->index('idMedia', 'fk_media');

            // foreign keys with matching column types
            $table->foreign('idEtudiant', 'fk_Etudiant')
                  ->references('id')->on('Etudiant')
                  ->onDelete('cascade');

            $table->foreign('idMedia', 'fk_media')
                  ->references('id')->on('Media')
                  ->onDelete('cascade');

            $table->foreign('idRole', 'fk_role')
                  ->references('id')->on('Role')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Participer');
    }
};
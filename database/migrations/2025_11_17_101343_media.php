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
        if (!Schema::hasTable('Media')) {
            Schema::create('Media', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedBigInteger('projet')->nullable()->index('fk_projet');
                $table->string('professeurReferent', 100)->nullable()->index('fk_professeur');
                $table->string('promotion', 300)->nullable();
                $table->string('description', 800)->nullable();
                $table->string('theme', 300)->nullable();
                $table->string('URI_NAS_PAD', 200)->nullable();
                $table->string('URI_NAS_ARCH', 200)->nullable();
                $table->string('URI_STOCKAGE_LOCAL', 200)->nullable();
                $table->string('mtd_tech_titre', 200);
                $table->string('mtd_tech_duree', 200);
                $table->string('mtd_tech_resolution', 200);
                $table->string('mtd_tech_fps', 200);
                $table->string('mtd_tech_format', 200);
                $table->timestamp('date_creation')->useCurrent();
                $table->timestamp('date_modification')->useCurrent();

                $table->foreign('professeurReferent', 'fk_professeur')
                      ->references('identifiant')->on('Professeur')
                      ->onDelete('set null');

                $table->foreign('projet', 'fk_projet')
                      ->references('id')->on('Projet')
                      ->onDelete('set null');
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

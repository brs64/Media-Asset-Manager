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
        Schema::table('medias', function (Blueprint $table) {
            // Statut du transcodage (en_attente, en_cours, termine, echoue, annule)
            $table->string('transcode_status')->nullable()->default('disponible');

            // ID du job FFAStrans
            $table->string('transcode_job_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medias', function (Blueprint $table) {
            $table->dropColumn([
                'transcode_status',
                'transcode_job_id',
            ]);
        });
    }
};

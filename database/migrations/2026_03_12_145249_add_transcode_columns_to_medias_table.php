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
            $table->string('transcode_status')->nullable()->default('en_attente');

            // ID du job FFAStrans
            $table->string('transcode_job_id')->nullable();

            // Progression du transcodage (0-100)
            $table->integer('transcode_progress')->nullable()->default(0);

            // Dates de début et fin du transcodage
            $table->timestamp('transcode_started_at')->nullable();
            $table->timestamp('transcode_finished_at')->nullable();

            // Message d'erreur en cas d'échec
            $table->text('transcode_error_message')->nullable();
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
                'transcode_progress',
                'transcode_started_at',
                'transcode_finished_at',
                'transcode_error_message'
            ]);
        });
    }
};

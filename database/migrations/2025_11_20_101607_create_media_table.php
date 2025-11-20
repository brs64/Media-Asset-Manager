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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('promotion')->nullable();
            $table->string('type')->nullable();
            $table->string('theme')->nullable();
            $table->text('description')->nullable();
            $table->string('mtd_tech_titre')->nullable();
            $table->string('mtd_tech_fps')->nullable();
            $table->string('mtd_tech_resolution')->nullable();
            $table->string('mtd_tech_duree')->nullable();
            $table->string('mtd_tech_format')->nullable();
            $table->string('URI_NAS_ARCH')->nullable();
            $table->string('URI_NAS_PAD')->nullable();
            $table->string('URI_NAS_MPEG')->nullable();
            $table->foreignId('projet_id')->constrained('projets')->onDelete('cascade');
            $table->foreignId('professeur_id')->constrained('professeurs')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};

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
        Schema::create('medias', function (Blueprint $table) {
            $table->id();
            $table->string('promotion')->nullable();
            $table->string('type')->nullable();
            $table->string('theme')->nullable();
            $table->text('description')->nullable();
            $table->string('mtd_tech_titre')->nullable();
            $table->string('URI_NAS_ARCH')->nullable();
            $table->string('URI_NAS_PAD')->nullable();
            $table->string('URI_NAS_MPEG')->nullable();
            $table->foreignId('professeur_id')->nullable()->constrained('professeurs')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medias');
    }
};

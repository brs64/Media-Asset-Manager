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
            // URIs need more space for long paths
            $table->string('URI_NAS_ARCH', 2048)->nullable()->change();
            $table->string('URI_NAS_PAD', 2048)->nullable()->change();
            $table->string('URI_NAS_MPEG', 2048)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medias', function (Blueprint $table) {
            $table->string('URI_NAS_ARCH', 255)->nullable()->change();
            $table->string('URI_NAS_PAD', 255)->nullable()->change();
            $table->string('URI_NAS_MPEG', 255)->nullable()->change();
        });
    }
};

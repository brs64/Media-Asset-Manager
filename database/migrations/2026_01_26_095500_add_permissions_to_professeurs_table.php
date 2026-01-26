<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('professeurs', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('prenom');
            $table->boolean('can_edit_video')->default(false)->after('is_admin');
            $table->boolean('can_broadcast_video')->default(false)->after('can_edit_video');
            $table->boolean('can_delete_video')->default(false)->after('can_broadcast_video');
            $table->boolean('can_administer')->default(false)->after('can_delete_video');
        });

        // Mettre à jour les professeurs existants qui sont admins
        // On suppose que le premier professeur est l'admin
        DB::table('professeurs')
            ->where('id', 1) // On suppose que le premier professeur est l'admin
            ->update([
                'is_admin' => true,
                'can_edit_video' => true,
                'can_broadcast_video' => true,
                'can_delete_video' => true,
                'can_administer' => true
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('professeurs', function (Blueprint $table) {
            $table->dropColumn([
                'is_admin',
                'can_edit_video',
                'can_broadcast_video',
                'can_delete_video',
                'can_administer'
            ]);
        });
    }
};

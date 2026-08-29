<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Właściciel węzła „Wspieramy" — sekcja per‑konto (jak shop_items.user_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiary_nodes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->index('user_id');
        });

        // Istniejące węzły -> właściciel admin (lula-marcin), fallback: pierwszy user.
        $ownerId = DB::table('users')->where('handle', 'lula-marcin')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');
        if ($ownerId) {
            DB::table('beneficiary_nodes')->whereNull('user_id')->update(['user_id' => $ownerId]);
        }
    }

    public function down(): void
    {
        Schema::table('beneficiary_nodes', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};

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

        // Istniejące węzły -> root owner (User::rootOwner() — patrz uwaga w
        // 2026_08_29_000004: NIE zgaduj tu handle na sztywno, różni się między
        // środowiskami (lokalnie „lula-marcin", produkcyjnie „marcin-lula").
        $ownerId = \App\Models\User::rootOwner()?->id;
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

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Właściciel produktu — sklepy per‑konto. Slug unikalny per‑użytkownik
 * (zamiast globalnie), bo każde konto ma niezależne produkty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_items', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->index('user_id');
        });

        // Istniejące produkty -> właściciel admin (lula-marcin), fallback: pierwszy user.
        $ownerId = DB::table('users')->where('handle', 'lula-marcin')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');
        if ($ownerId) {
            DB::table('shop_items')->whereNull('user_id')->update(['user_id' => $ownerId]);
        }

        // Slug unikalny w obrębie sklepu (user_id, slug), nie globalnie.
        // Uwaga: dropIndex po nazwie (nie dropUnique) — przenośne. Na Postgresie
        // unik slug bywa INDEKSEM (nie constraintem, gdy tworzony przez Liquibase),
        // więc dropUnique -> DROP CONSTRAINT nie działa; dropIndex -> DROP INDEX działa
        // i na Postgresie, i na MySQL/MariaDB.
        Schema::table('shop_items', function (Blueprint $table) {
            $table->dropIndex('shop_items_slug_unique');
            $table->unique(['user_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('shop_items', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'slug']);
            $table->unique(['slug']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};

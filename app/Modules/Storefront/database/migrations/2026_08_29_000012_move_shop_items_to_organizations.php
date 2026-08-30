<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Właściciel produktu -> Organization zamiast User bezpośrednio. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_items', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        DB::statement('UPDATE shop_items SET organization_id = (SELECT id FROM organizations WHERE organizations.user_id = shop_items.user_id)');

        Schema::table('shop_items', function (Blueprint $table) {
            // UWAGA: w odróżnieniu od `shop_items_slug_unique` w
            // 2026_07_06_000011 (ten był INDEKSEM z Liquibase), ten unique
            // ZOSTAŁ utworzony przez Laravela (`$table->unique(...)` w tej
            // samej migracji 000011) — na Postgresie to prawdziwy CONSTRAINT,
            // więc trzeba dropUnique() (DROP CONSTRAINT), nie dropIndex()
            // (DROP INDEX się nie uda: „constraint ... requires it").
            $table->dropUnique('shop_items_user_id_slug_unique');
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
            $table->unique(['organization_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('shop_items', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'slug']);
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->index('user_id');
        });
        DB::statement('UPDATE shop_items SET user_id = (SELECT user_id FROM organizations WHERE organizations.id = shop_items.organization_id)');
        Schema::table('shop_items', function (Blueprint $table) {
            $table->unique(['user_id', 'slug']);
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};

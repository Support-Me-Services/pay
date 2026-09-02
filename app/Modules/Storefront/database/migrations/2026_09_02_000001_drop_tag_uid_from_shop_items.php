<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refaktor tagów NFC do modułu Init (patrz app/Modules/Init) — tag_uid
 * przenosi się do init_codes, ta kolumna znika. MUSI wejść PO migracji
 * danych w app/Modules/Init/database/migrations (kopiuje istniejące
 * tag_uid do init_codes zanim tu zginą) — patrz kolejność w bin/deploy.sh
 * / docker/entrypoint.sh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_items', function (Blueprint $table) {
            // Unique utworzony przez Laravela w 2026_06_17_000001 (nie przez
            // Liquibase) -> na Postgresie to prawdziwy CONSTRAINT, więc
            // dropUnique() (DROP CONSTRAINT), NIE dropIndex() (patrz pamięć
            // pay-postgres-unique-drop / DEPLOYMENT.md).
            $table->dropUnique('shop_items_tag_uid_unique');
            $table->dropColumn('tag_uid');
        });
    }

    public function down(): void
    {
        Schema::table('shop_items', function (Blueprint $table) {
            $table->string('tag_uid')->nullable()->unique();
        });
    }
};

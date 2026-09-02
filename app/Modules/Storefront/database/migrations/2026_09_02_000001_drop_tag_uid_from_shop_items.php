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
            // POPRAWKA po nieudanym wdrożeniu na produkcji: mimo że
            // 2026_06_17_000001 tworzy ten unique przez $table->unique()
            // (co zwykle na Postgresie oznacza CONSTRAINT), schemat
            // Storefrontu na produkcji budowany jest przez Liquibase, NIE
            // przez tę migrację (patrz docker/entrypoint.sh) — tam ten
            // unique jest zwykłym INDEKSEM. dropUnique() (DROP CONSTRAINT)
            // rzuca tam SQLSTATE 42704 "constraint ... does not exist".
            // dropIndex() (DROP INDEX) — patrz pamięć pay-postgres-unique-drop.
            $table->dropIndex('shop_items_tag_uid_unique');
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

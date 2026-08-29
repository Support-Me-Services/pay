<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mecenas na Zbiórce = wybór istniejącej organizacji, nie wolny tekst.
 * Kolumny mecenas_name/mecenas_url/mecenas_logo (dodane w tej samej sesji,
 * jeszcze niewdrożone) zastępujemy relacją zamiast dokładać kolejną migrację
 * "dodaj FK obok" — nic realnego jeszcze na produkcji nie było ustawione.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_items', function (Blueprint $table) {
            $table->dropColumn(['mecenas_name', 'mecenas_url', 'mecenas_logo']);
            $table->foreignId('mecenas_organization_id')->nullable()->after('thank_you_image')
                ->constrained('organizations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shop_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mecenas_organization_id');
            $table->string('mecenas_name')->nullable();
            $table->string('mecenas_url')->nullable();
            $table->string('mecenas_logo')->nullable();
        });
    }
};

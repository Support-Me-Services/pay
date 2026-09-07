<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faza 3 migracji Organization -> org-svc: shop_items przenosi się całkowicie
 * do org-svc. Order (moduł Gateway, zostaje w Laravelu) trzyma shop_item_id
 * wyłącznie jako referencję do ID w org-svc — kolumna zostaje (potrzebna do
 * strony podziękowania), ale realny klucz obcy znika (target nie jest już
 * lokalną tabelą). Musi wejść PRZED dropem tabeli shop_items.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shop_item_id']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('shop_item_id')->references('id')->on('shop_items')->nullOnDelete();
        });
    }
};

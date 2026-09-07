<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faza 3 migracji Organization -> org-svc: ShopItem przeniesiony całkowicie
 * (dane + CRUD) do org-svc. Dane zweryfikowane 1:1 w org-svc PRZED tym
 * dropem. Wymaga wcześniejszej migracji 2026_09_06_000002 (FK z orders).
 *
 * Dodatkowo (Faza 5): init_codes.shop_item_id też ma FK do shop_items —
 * musi zniknąć PRZED dropem tabeli, inaczej MySQL odmówi. Kolumna zostaje
 * (init_codes samo jeszcze nie jest zmigrowane w MySQL — patrz
 * 2026_09_07_000002_drop_init_codes.php), traci tylko realny klucz obcy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('init_codes', function (Blueprint $table) {
            $table->dropForeign('init_codes_shop_item_id_foreign');
        });

        Schema::dropIfExists('shop_items');
    }

    public function down(): void
    {
        // Nieodwracalne — dane żyją teraz wyłącznie w org-svc.
    }
};

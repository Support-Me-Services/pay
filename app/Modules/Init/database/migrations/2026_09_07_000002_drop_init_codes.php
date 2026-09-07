<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Faza 5 migracji Organization -> org-svc: InitCode przeniesiony całkowicie
 * (dane + CRUD + rozwiązywanie skanów) do core-svc. Dane zweryfikowane 1:1
 * w core-svc PRZED tym dropem. Wymaga wcześniejszej migracji
 * app/Modules/Storefront/database/migrations/2026_09_06_000003_drop_shop_items.php
 * (usuwa FK shop_item_id->shop_items, inaczej ta migracja by się nie
 * uruchomiła w innej kolejności — ale obie i tak idą w jednym `migrate`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('init_codes');
    }

    public function down(): void
    {
        // Nieodwracalne — dane żyją teraz wyłącznie w core-svc.
    }
};

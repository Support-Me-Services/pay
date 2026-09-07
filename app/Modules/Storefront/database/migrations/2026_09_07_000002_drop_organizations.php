<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Faza 6 (finalna) migracji Organization -> org-svc: po tym, jak wszystkie
 * 5 tabel zależnych (beneficiary_nodes, shop_items, job_positions,
 * job_applications, init_codes) zostały już zdjęte, `organizations` nie ma
 * już ŻADNEGO lokalnego klucza obcego wskazującego na nią — cały kod
 * czytający/piszący organizacje przeszedł na org-svc (patrz
 * app/Modules/Storefront/Models/Organization.php, już nie Eloquent).
 * Dane w org-svc są od Fazy 1 źródłem prawdy (ta tabela była tylko
 * zsynchronizowanym lustrem dla odczytu) — bezpieczne do usunięcia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('organizations');
    }

    public function down(): void
    {
        // Nieodwracalne — dane żyją teraz wyłącznie w org-svc.
    }
};

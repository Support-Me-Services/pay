<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Faza 2 migracji Organization -> org-svc: BeneficiaryNode przeniesiony
 * całkowicie (dane + CRUD) do org-svc — nic lokalnie nie ma FK do tej
 * tabeli (w odróżnieniu od organizations), więc pełny drop bez lustra.
 * Dane zweryfikowane 1:1 w org-svc PRZED tym dropem (patrz plan migracji).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('beneficiary_nodes');
    }

    public function down(): void
    {
        // Nieodwracalne — dane żyją teraz wyłącznie w org-svc.
    }
};

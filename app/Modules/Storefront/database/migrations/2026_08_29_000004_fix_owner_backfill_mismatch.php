<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Naprawa błędnego backfillu z 2026_08_29_00000{1,2,3}: te migracje zgadywały
 * root ownera przez handle na sztywno wpisany 'lula-marcin' (nazwa z lokalnego
 * dev seedu) — na produkcji prawdziwy handle to 'marcin-lula', więc fallback
 * nie trafił i dane przypisały się do przypadkowego pierwszego konta w bazie
 * zamiast do właściwego właściciela (User::rootOwner()).
 *
 * Bezpieczne do uruchomienia wszędzie (idempotentne): przed tą funkcją WSZYSTKIE
 * wiersze w tych trzech tabelach i tak konceptualnie należały do jednego,
 * niejawnego właściciela (funkcja per-konto nie istniała) — więc jednolite
 * przypisanie do prawdziwego root ownera jest poprawne, nie nadpisuje żadnych
 * innych, legalnie odrębnych danych tenant-owych (jeszcze ich nie było).
 */
return new class extends Migration
{
    public function up(): void
    {
        $rootOwnerId = \App\Models\User::rootOwner()?->id;

        if (! $rootOwnerId) {
            return;
        }

        DB::table('beneficiary_nodes')->update(['user_id' => $rootOwnerId]);
        DB::table('job_positions')->update(['user_id' => $rootOwnerId]);
        DB::table('job_applications')->update(['user_id' => $rootOwnerId]);
    }

    public function down(): void
    {
        // Nieodwracalne (nie znamy poprzedniego, błędnego stanu) — no-op.
    }
};

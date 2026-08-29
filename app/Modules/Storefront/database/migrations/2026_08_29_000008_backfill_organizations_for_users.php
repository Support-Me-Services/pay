<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Jedna organizacja per istniejące konto (User) — tak, żeby żadne dotychczasowe
 * konto nie zostało bez organizacji, gdy kolejne migracje przenoszą własność
 * O nas/Zbiórek/Pracy/Aplikacji z user_id na organization_id.
 * Handle organizacji = handle usera (już globalnie unikalny -> bezpieczne).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (DB::table('users')->orderBy('id')->get() as $user) {
            DB::table('organizations')->insert([
                'user_id' => $user->id,
                'name' => $user->name,
                'handle' => $user->handle ?? ('org-' . $user->id),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Nieodwracalne (dane mogły się już zmienić po backfillu) — no-op.
    }
};

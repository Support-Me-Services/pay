<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Logowanie ma być niewrażliwe na wielkość liter w e-mailu (patrz
 * App\Models\User::email() — nowe zapisy trafiają już małymi literami) —
 * jednorazowo normalizuje istniejące konta założone przed tą zmianą.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->update(['email' => DB::raw('LOWER(email)')]);
    }

    public function down(): void
    {
        // Nieodwracalne (oryginalna wielkość liter nie jest nigdzie zachowana).
    }
};

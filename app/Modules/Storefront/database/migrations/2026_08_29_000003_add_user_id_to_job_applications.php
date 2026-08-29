<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Właściciel zgłoszenia rekrutacyjnego — sekcja „Aplikacje"/„Baza kandydatów"
 * per‑konto. Ustawiony bezpośrednio (nie tylko przez job_position_id), bo
 * aplikacja spontaniczna (job_position_id = null) też musi mieć właściciela.
 * MUSI migrować się po dodaniu user_id do job_positions (kolejność przez
 * nazwę pliku/timestamp).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->index('user_id');
        });

        $rootOwnerId = DB::table('users')->where('handle', 'lula-marcin')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        // Bez surowego JOIN UPDATE (niezgodne między MySQL a PostgreSQL) —
        // mapa właścicieli ofert, potem prosty update po id (przenośne).
        $positionOwners = DB::table('job_positions')->pluck('user_id', 'id');
        DB::table('job_applications')->whereNotNull('job_position_id')->whereNull('user_id')
            ->orderBy('id')->select('id', 'job_position_id')
            ->chunkById(200, function ($rows) use ($positionOwners) {
                foreach ($rows as $row) {
                    $ownerId = $positionOwners[$row->job_position_id] ?? null;
                    if ($ownerId) {
                        DB::table('job_applications')->where('id', $row->id)->update(['user_id' => $ownerId]);
                    }
                }
            });

        // Aplikacje spontaniczne (bez oferty) lub bez rozwiązanego właściciela -> root owner.
        if ($rootOwnerId) {
            DB::table('job_applications')->whereNull('user_id')->update(['user_id' => $rootOwnerId]);
        }
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};

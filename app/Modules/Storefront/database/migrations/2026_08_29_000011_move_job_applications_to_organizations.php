<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Właściciel zgłoszenia -> Organization zamiast User bezpośrednio.
 * job_applications.user_id już poprawnie odzwierciedla właściciela (ustawiony
 * w 2026_08_29_000003 — z oferty albo root owner dla zgłoszeń spontanicznych),
 * więc backfill jest prostym mapowaniem user_id -> organizations.id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        DB::statement('UPDATE job_applications SET organization_id = (SELECT id FROM organizations WHERE organizations.user_id = job_applications.user_id)');

        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->index('user_id');
        });
        DB::statement('UPDATE job_applications SET user_id = (SELECT user_id FROM organizations WHERE organizations.id = job_applications.organization_id)');
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};

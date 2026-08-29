<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Właściciel oferty pracy -> Organization zamiast User bezpośrednio. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_positions', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        DB::statement('UPDATE job_positions SET organization_id = (SELECT id FROM organizations WHERE organizations.user_id = job_positions.user_id)');

        Schema::table('job_positions', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('job_positions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->index('user_id');
        });
        DB::statement('UPDATE job_positions SET user_id = (SELECT user_id FROM organizations WHERE organizations.id = job_positions.organization_id)');
        Schema::table('job_positions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};

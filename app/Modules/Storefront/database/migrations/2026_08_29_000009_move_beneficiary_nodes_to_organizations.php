<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Właściciel węzła „O nas" -> Organization zamiast User bezpośrednio. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiary_nodes', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        // Subquery skorelowany — przenośny (MySQL i PostgreSQL), bez JOIN UPDATE.
        DB::statement('UPDATE beneficiary_nodes SET organization_id = (SELECT id FROM organizations WHERE organizations.user_id = beneficiary_nodes.user_id)');

        Schema::table('beneficiary_nodes', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('beneficiary_nodes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->index('user_id');
        });
        DB::statement('UPDATE beneficiary_nodes SET user_id = (SELECT user_id FROM organizations WHERE organizations.id = beneficiary_nodes.organization_id)');
        Schema::table('beneficiary_nodes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};

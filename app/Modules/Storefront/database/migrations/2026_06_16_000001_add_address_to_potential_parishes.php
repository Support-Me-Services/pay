<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adres ulicy parafii (ulica + nr, bez miasta) — dataset OSM go nie zawiera.
        // Uzupełniany ze źródeł teleadresowych (deon.pl, schematyzmy diecezjalne,
        // Wikipedia) komendą parishes:enrich, COALESCE — tylko gdy puste.
        Schema::table('potential_parishes', function (Blueprint $table) {
            $table->string('address')->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('potential_parishes', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};

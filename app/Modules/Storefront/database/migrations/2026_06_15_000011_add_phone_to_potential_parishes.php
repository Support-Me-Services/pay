<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Telefon parafii — dataset OSM nie zawiera numerów, więc pole startowo puste.
        // Operator uzupełnia je inline, dzwoniąc (auto-zapis przez AJAX w panelu CRM).
        Schema::table('potential_parishes', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('denomination');
        });
    }

    public function down(): void
    {
        Schema::table('potential_parishes', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};

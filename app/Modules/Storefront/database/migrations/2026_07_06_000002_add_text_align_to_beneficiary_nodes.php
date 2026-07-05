<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wyrównanie tekstu w węźle „Wspieramy": left | center | right.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiary_nodes', function (Blueprint $table) {
            $table->string('text_align', 10)->default('left')->after('image_side');
        });
    }

    public function down(): void
    {
        Schema::table('beneficiary_nodes', function (Blueprint $table) {
            $table->dropColumn('text_align');
        });
    }
};

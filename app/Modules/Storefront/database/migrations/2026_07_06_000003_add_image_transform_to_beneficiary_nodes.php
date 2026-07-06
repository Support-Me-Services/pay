<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kadrowanie grafiki w kole: skala (zoom, %) i przesunięcie (poziomo/pionowo, %).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiary_nodes', function (Blueprint $table) {
            $table->unsignedSmallInteger('image_scale')->default(100)->after('image_side'); // % (100 = dopasowanie)
            $table->smallInteger('image_x')->default(0)->after('image_scale');               // przesunięcie poziome %
            $table->smallInteger('image_y')->default(0)->after('image_x');                   // przesunięcie pionowe %
        });
    }

    public function down(): void
    {
        Schema::table('beneficiary_nodes', function (Blueprint $table) {
            $table->dropColumn(['image_scale', 'image_x', 'image_y']);
        });
    }
};

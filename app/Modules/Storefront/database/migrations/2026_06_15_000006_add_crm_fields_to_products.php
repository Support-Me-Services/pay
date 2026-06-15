<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rozszerzenie produktów (parafii) o pola CRM:
        // dane kontaktowe, województwo, status leada i przypisany handlowiec.
        Schema::table('products', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('city');
            $table->string('website')->nullable()->after('phone');
            $table->string('voivodeship')->nullable()->after('website');   // województwo
            $table->string('status', 20)->default('kontakt')->after('active');
            $table->unsignedBigInteger('salesperson_id')->nullable()->after('status');

            $table->foreign('salesperson_id')
                ->references('id')->on('salespeople')
                ->nullOnDelete();

            $table->index('status');
        });

        // Istniejące parafie są już na żywo (publiczne) — oznacz je jako aktywne.
        \Illuminate\Support\Facades\DB::table('products')->update(['status' => 'aktywna']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['salesperson_id']);
            $table->dropIndex(['status']);
            $table->dropColumn(['phone', 'website', 'voivodeship', 'status', 'salesperson_id']);
        });
    }
};

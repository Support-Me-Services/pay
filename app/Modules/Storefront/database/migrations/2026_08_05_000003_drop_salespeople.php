<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Usunięcie panelu „Handlowcy". Produkty (parafie) zostają — tylko tracą
     * przypisanie handlowca (kolumna i jej FK są usuwane razem z tabelą, w tej
     * kolejności, żeby FK nie blokował dropu `salespeople`).
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['salesperson_id']);
            $table->dropColumn('salesperson_id');
        });

        Schema::dropIfExists('salespeople');
    }

    public function down(): void
    {
        Schema::create('salespeople', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('voivodeships')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('salesperson_id')->nullable()->after('status');
            $table->foreign('salesperson_id')->references('id')->on('salespeople')->nullOnDelete();
        });
    }
};

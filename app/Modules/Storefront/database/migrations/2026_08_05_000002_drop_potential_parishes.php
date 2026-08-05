<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Usunięcie panelu „Parafie do obdzwonienia" i „Mapa pokrycia" (oba czytały
     * tę tabelę). Musi zejść przed dropem `salespeople` — kolumna salesperson_id
     * ma do niej FK.
     */
    public function up(): void
    {
        Schema::dropIfExists('potential_parishes');
    }

    public function down(): void
    {
        Schema::create('potential_parishes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('voivodeship')->nullable();
            $table->string('denomination')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lon', 10, 7);
            $table->string('status')->default('nowa');
            $table->foreignId('salesperson_id')->nullable()->constrained('salespeople')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->timestamps();

            $table->index('voivodeship');
            $table->index('city');
            $table->index('status');
        });
    }
};

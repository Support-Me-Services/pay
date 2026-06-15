<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lista potencjalnych parafii do obdzwonienia (CRM dla handlowców) —
        // dataset ~20 tys. parafii zescrapowany z OpenStreetMap. Import: parishes:import.
        Schema::create('potential_parishes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('voivodeship')->nullable();
            $table->string('denomination')->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lon', 10, 7);
            // Lejek CRM: nowa | do_obdzwonienia | zadzwoniono | zainteresowana | odrzucona | dodana
            $table->string('status')->default('nowa');
            // Handlowiec prowadzący lead — FK do salespeople (nullOnDelete jak przy produktach).
            $table->foreignId('salesperson_id')->nullable()->constrained('salespeople')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->timestamps();

            $table->index('voivodeship');
            $table->index('city');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('potential_parishes');
    }
};

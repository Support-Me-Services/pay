<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Organizacja — byt nad kontem (User): jedno konto może zarządzać wieloma
 * organizacjami. Właściciel 5 sekcji (O nas/Zbiórki/Praca/Aplikacje/Baza
 * kandydatów) — patrz kolejne migracje przenoszące tam user_id -> organization_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('handle')->unique();
            $table->string('logo')->nullable();
            // NULL = wszystkie sekcje widoczne (domyślnie, self-service toggle).
            $table->json('enabled_sections')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};

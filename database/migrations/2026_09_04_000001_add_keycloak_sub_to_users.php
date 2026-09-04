<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faza 6 — Laravel przestaje mieć własne uwierzytelnianie, logowanie idzie
 * przez Keycloak. `keycloak_sub` to jedyny sposób dopasowania konta lokalnego
 * do tożsamości Keycloaka — CELOWO nie e-mail (patrz claude/marcin, sekcja
 * Faza 6: dopasowanie po e-mailu przy niezweryfikowanym adresie to realna
 * dziura na przejęcie konta). `password` przestaje być wymagane — konta
 * zakładane przez Keycloak nie mają lokalnego hasła w ogóle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('keycloak_sub')->nullable()->unique()->after('id');
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('keycloak_sub');
            $table->string('password')->nullable(false)->change();
        });
    }
};

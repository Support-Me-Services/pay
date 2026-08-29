<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Organizacja musi zawsze mieć jakiegoś administrującego użytkownika — nigdy
 * nie może zostać osierocona. cascadeOnDelete pozwalało dziś, żeby usunięcie
 * konta po cichu skasowało wszystkie jego organizacje (i kaskadowo ich
 * Zbiórki/„O nas"/Pracę). Zamiast tego: usunięcie użytkownika z organizacjami
 * ma się nie udać na poziomie bazy, dopóki aplikacja (User::booted, zdarzenie
 * `deleting`) nie przepnie ich najpierw na innego użytkownika.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};

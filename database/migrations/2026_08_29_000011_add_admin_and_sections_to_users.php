<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Super-user (is_admin, ustawiane ręcznie w bazie — bez samoobsługowej
 * promocji) + widoczność sekcji panelu per-konto.
 *
 * enabled_sections = NULL oznacza "wszystkie sekcje widoczne" — zachowanie
 * identyczne z dotychczasowym, dopóki super-user czegoś nie ograniczy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('handle');
            $table->json('enabled_sections')->nullable()->after('is_admin');
        });

        DB::table('users')->where('email', 'marcin.lula@please-support-me.com')->update(['is_admin' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_admin', 'enabled_sections']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Handle konta = slug sklepu użytkownika (sklep pod /user/{handle}).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('handle')->nullable()->unique()->after('name');
        });

        // Konto admin@local -> sklep pod /user/lula-marcin.
        DB::table('users')->where('email', 'admin@local')->update([
            'name' => 'Marcin Lula',
            'handle' => 'lula-marcin',
        ]);

        // Pozostali istniejący użytkownicy bez handle -> unikalny slug z nazwy.
        foreach (DB::table('users')->whereNull('handle')->get(['id', 'name']) as $u) {
            $base = Str::slug($u->name) ?: 'sklep';
            $handle = $base;
            $i = 2;
            while (DB::table('users')->where('handle', $handle)->exists()) {
                $handle = $base.'-'.$i++;
            }
            DB::table('users')->where('id', $u->id)->update(['handle' => $handle]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['handle']);
            $table->dropColumn('handle');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dane darczyńcy (imię, nazwisko, e-mail) — wymóg PayU: metoda płatności
 * app2app musi zbierać te dane przed utworzeniem zamówienia u operatora.
 * Nullable — istniejące transakcje ich nie mają; od teraz wymagane na
 * poziomie walidacji w PaymentController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('buyer_first_name')->nullable()->after('product_name');
            $table->string('buyer_last_name')->nullable()->after('buyer_first_name');
            $table->string('buyer_email')->nullable()->after('buyer_last_name');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['buyer_first_name', 'buyer_last_name', 'buyer_email']);
        });
    }
};

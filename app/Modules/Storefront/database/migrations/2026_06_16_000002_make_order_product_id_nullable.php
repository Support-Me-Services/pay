<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zamówienia ze sklepu firmowego (koszyk) nie są związane z pojedynczym
 * produktem z tabeli `products` (merch jest statyczny) — product_id musi
 * dopuszczać NULL. Klucz obcy (jeśli istnieje) pozostaje dla zamówień parafii.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });
    }
};

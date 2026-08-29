<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Usunięcie struktury „Parafie" (Product) — orders.product_id jest usuwane
 * PRZED tabelą products (kolejność zależności FK). orders.shop_item_id
 * zostaje jedynym wskazaniem na przedmiot zamówienia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('id')->constrained();
        });
    }
};

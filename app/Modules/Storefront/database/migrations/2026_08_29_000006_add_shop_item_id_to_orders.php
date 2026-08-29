<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Które ShopItem dotyczyło zamówienia — potrzebne, żeby po płatności pokazać
 * WŁAŚCIWĄ, definiowalną stronę podziękowania (patrz shop_items.thank_you_*).
 * Mirror istniejącego orders.product_id (nullable, nullOnDelete).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shop_item_id')->nullable()->after('product_id')
                ->constrained('shop_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shop_item_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Usunięcie struktury „Parafie" (Product) — najstarszy, sprzed-organizacyjny
 * model donacyjny (NFC-tag -> strona produktu -> dowolna kwota). Zastąpiony
 * przez per-organizacyjne Zbiórki (ShopItem). Dane w tych tabelach to
 * wyłącznie dane demo (ChurchSeeder/ProductsSeeder) — usuwamy bez migracji
 * danych. orders.product_id już odcięte w poprzedniej migracji.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('parish_notes');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('events');
        Schema::dropIfExists('products');
    }

    public function down(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('voivodeship')->nullable();
            $table->string('purpose')->nullable();
            $table->string('slug')->unique();
            $table->text('description_html')->nullable();
            $table->text('pickup_instruction')->nullable();
            $table->unsignedInteger('price');
            $table->string('tag_uid')->unique();
            $table->string('main_image')->nullable();
            $table->boolean('active')->default(true);
            $table->string('status', 20)->default('kontakt');
            $table->timestamp('created_at')->useCurrent();
            $table->index('status');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('sort')->default(0);
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['tag_open', 'page_view', 'buy_click', 'purchase']);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['product_id', 'type', 'created_at']);
        });

        Schema::create('parish_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->string('type', 20)->default('kontakt');
            $table->string('author')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('product_id');
        });
    }
};

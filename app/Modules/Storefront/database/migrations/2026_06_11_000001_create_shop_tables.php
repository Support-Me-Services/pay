<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Pola parafialne (tryb church/Taca) — nullable, nieszkodliwe dla products.
            $table->string('city')->nullable();
            $table->string('purpose')->nullable();
            $table->string('slug')->unique();
            $table->text('description_html')->nullable();
            $table->text('pickup_instruction')->nullable();
            $table->unsignedInteger('price'); // grosze
            $table->string('tag_uid')->unique();
            $table->string('main_image')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('sort')->default(0);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('product_id')->constrained();
            $table->uuid('transaction_id')->nullable(); // uuid z bramki
            $table->unsignedInteger('amount'); // grosze
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('transaction_id');
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['tag_open', 'page_view', 'buy_click', 'purchase']);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['product_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
    }
};

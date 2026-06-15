<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('base_url');
            $table->string('api_key', 64)->unique();
            $table->enum('payment_mode', ['classic', 'app2app'])->default('classic');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('tag_uid')->unique();
            $table->string('target_url');
            $table->string('label')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('shop_id')->constrained();
            $table->foreignId('tag_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_external_id');
            $table->string('product_name');
            $table->unsignedInteger('amount'); // grosze
            $table->string('currency', 3)->default('PLN');
            $table->enum('status', ['created', 'pending', 'paid', 'failed', 'abandoned'])->default('created');
            $table->enum('mode', ['classic', 'app2app']);
            $table->string('return_url', 500);
            $table->string('notify_url', 500)->nullable();
            $table->string('provider_order_id')->nullable();
            $table->string('provider_redirect_url', 1000)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['shop_id', 'status']);
            $table->index('created_at');
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('transaction_id')->nullable();
            $table->enum('type', ['tag_open', 'payment_started', 'payment_success', 'payment_failed']);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['shop_id', 'type', 'created_at']);
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('company')->nullable();
            $table->text('message');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('antitheft_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['ok', 'warning'])->default('ok');
            $table->unsignedInteger('foreign_tags_found')->default(0); // zawsze 0 — moduł demo
            $table->timestamp('checked_at');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antitheft_checks');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('events');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('shops');
    }
};

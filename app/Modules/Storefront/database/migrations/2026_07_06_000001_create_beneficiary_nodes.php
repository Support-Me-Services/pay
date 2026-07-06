<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Węzły podstrony „Wspieramy" (/beneficiaries) — edytowalne z panelu.
 * Każdy węzeł: nagłówek + grafika (po lewej/prawej) + tekst (rich text HTML).
 * Kolejność (position) ustawiana przeciąganiem w panelu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('heading');
            $table->string('image')->nullable();            // ścieżka grafiki (storage)
            $table->string('image_side', 10)->default('left'); // left | right
            $table->longText('body_html')->nullable();      // treść (rich text z Quill)
            $table->unsignedInteger('position')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_nodes');
    }
};

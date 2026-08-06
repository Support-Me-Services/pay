<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Usunięcie panelu „Kategorie" i sekcji kategorii na /kategoria/{slug} —
     * sekcja „Kogo wspieramy?" renderuje teraz treść BeneficiaryNode inline
     * (zob. StorefrontController::index()), zamiast kafelków z tej tabeli.
     */
    public function up(): void
    {
        Schema::dropIfExists('categories');
    }

    public function down(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('label');
            $table->text('label_html')->nullable();
            $table->string('label_text');
            $table->text('intro')->nullable();
            $table->string('icon')->nullable();
            $table->string('source', 20)->default('none');
            $table->integer('position')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('parent_id');
            $table->index('position');
        });
    }
};

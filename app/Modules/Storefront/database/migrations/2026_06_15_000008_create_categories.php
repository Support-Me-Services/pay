<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kategorie wsparcia (sekcja „Kogo wspieramy?") — drzewo edytowalne z panelu.
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            // Rodzic (zagnieżdżenie). NULL = pozycja top-level.
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('label');
            // Wersja etykiety z dopuszczalnym <br> (render na landingu).
            $table->text('label_html')->nullable();
            $table->string('label_text');
            $table->text('intro')->nullable();
            // Ścieżka ikonki w storage (np. 'category-icons/x.png'); NULL = pusty dysk.
            $table->string('icon')->nullable();
            // Źródło pozycji listowanych na stronie kategorii.
            $table->string('source', 20)->default('none'); // none | parishes
            $table->integer('position')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('parent_id');
            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};

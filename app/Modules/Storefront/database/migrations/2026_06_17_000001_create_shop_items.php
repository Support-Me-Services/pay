<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Produkty sklepu donacyjnego (NFC) — osobno od `products` (parafie/Taca).
 * Każdy item ma minimalną kwotę (grosze), opcjonalny tag NFC kierujący na
 * stronę sklepu, oraz flagę domyślnego produktu („Serduszko"), który pokazuje
 * się w modalu po wejściu na stronę.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_items', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('image')->nullable();           // ścieżka do grafiki (lub SVG serduszka)
            $table->unsignedInteger('min_amount');          // minimalna kwota w groszach
            $table->boolean('is_default')->default(false);  // „Serduszko" — domyślny produkt (auto-modal)
            $table->string('tag_uid')->nullable()->unique();// UID taga NFC kierującego na sklep
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort')->default(0);    // kolejność na liście
            $table->timestamps();
        });

        // Produkty startowe: domyślne „Serduszko" (1 zł) + merch (po 1 zł min.).
        $now = now();
        DB::table('shop_items')->insert([
            ['slug' => 'serduszko',          'name' => 'Serduszko',          'image' => 'img/sklep/heart-wesprzyj.svg', 'min_amount' => 100, 'is_default' => true,  'tag_uid' => null, 'active' => true, 'sort' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'kubek-supportme',    'name' => 'Kubek SupportMe',    'image' => 'img/sklep/kubek.jpg',          'min_amount' => 100, 'is_default' => false, 'tag_uid' => null, 'active' => true, 'sort' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'koszulka-supportme', 'name' => 'Koszulka SupportMe', 'image' => 'img/sklep/koszulka.jpg',       'min_amount' => 100, 'is_default' => false, 'tag_uid' => null, 'active' => true, 'sort' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'pin-supportme',      'name' => 'Pin SupportMe',      'image' => 'img/sklep/pin.jpg',            'min_amount' => 100, 'is_default' => false, 'tag_uid' => null, 'active' => true, 'sort' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'brelok-supportme',   'name' => 'Brelok SupportMe',   'image' => 'img/sklep/brelok.jpg',         'min_amount' => 100, 'is_default' => false, 'tag_uid' => null, 'active' => true, 'sort' => 4, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_items');
    }
};

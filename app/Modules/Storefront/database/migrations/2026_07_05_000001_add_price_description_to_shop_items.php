<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wariant sklepowy (branch sklep-payu): produkty NFC dostają STAŁĄ cenę
 * i opis, żeby spełnić wymogi PayU (oferta: opis + cena + zdjęcie).
 * Kolumny są dodatkowe i nullable — model darowiznowy (min_amount) na
 * innych gałęziach nie jest ruszany; `price` z fallbackiem do `min_amount`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_items', function (Blueprint $table) {
            $table->unsignedInteger('price')->nullable()->after('min_amount'); // cena stała w groszach
            $table->text('description')->nullable()->after('price');           // opis produktu (oferta)
        });

        // Backfill: brakująca cena = dotychczasowe minimum (nic nie zostaje puste).
        DB::table('shop_items')->whereNull('price')->update(['price' => DB::raw('min_amount')]);

        // Realistyczne ceny + opisy dla produktów startowych.
        $seed = [
            'serduszko'          => [900,  'Naklejka NFC „Serduszko” — przyklej i przyjmuj wsparcie jednym zbliżeniem telefonu. Programowalny tag NFC w formie serca.'],
            'kubek-supportme'    => [3900, 'Ceramiczny kubek SupportMe 330 ml z nadrukiem logo. Nadaje się do zmywarki i mikrofalówki.'],
            'koszulka-supportme' => [6900, 'Bawełniana koszulka SupportMe (100% bawełna organiczna) z nadrukiem logo. Dostępne rozmiary S–XXL.'],
            'pin-supportme'      => [1500, 'Metalowa przypinka (pin) SupportMe z zapięciem motylkowym. Średnica 25 mm.'],
            'brelok-supportme'   => [1900, 'Brelok NFC SupportMe — programowalny tag zbliżeniowy w obudowie z brelokiem do kluczy.'],
        ];
        foreach ($seed as $slug => [$price, $desc]) {
            DB::table('shop_items')->where('slug', $slug)->update([
                'price'       => $price,
                'min_amount'  => $price,      // spójność: w trybie sklepu min = cena
                'description' => $desc,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('shop_items', function (Blueprint $table) {
            $table->dropColumn(['price', 'description']);
        });
    }
};

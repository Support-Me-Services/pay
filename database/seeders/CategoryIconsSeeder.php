<?php

namespace Database\Seeders;

use App\Modules\Storefront\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Ustawia obrazki (kolumna `icon`) dla 6 kategorii sekcji „Kogo wspieramy?".
 * Ścieżki są względem dysku public (storage/app/public) — render w widoku:
 *   <img class="lp-cat__disc-img" src="{{ asset('storage/'.$cat['icon']) }}">
 *
 * Pliki leżą w storage/app/public/category-icons/<slug>.jpg.
 * Idempotentny: aktualizuje wyłącznie kolumnę `icon` po slug, nie tworzy rekordów.
 *
 * Uruchom dla bazy church:
 *   TENANT=please-support-me.com php artisan db:seed --class=Database\\Seeders\\CategoryIconsSeeder
 */
class CategoryIconsSeeder extends Seeder
{
    /** Slug => ścieżka pliku względem dysku public. */
    private const ICONS = [
        'organizacje-spoleczne'          => 'category-icons/organizacje-spoleczne.jpg',
        'fundacje-i-stowarzyszenia'      => 'category-icons/fundacje-i-stowarzyszenia.jpg',
        'wspolnoty-lokalne'              => 'category-icons/wspolnoty-lokalne.jpg',
        'miejsca-kultu'                  => 'category-icons/miejsca-kultu.jpg',
        'inicjatywy-charytatywne'        => 'category-icons/inicjatywy-charytatywne.jpg',
        'projekty-spoleczne-i-edukacyjne' => 'category-icons/projekty-spoleczne-i-edukacyjne.jpg',
    ];

    public function run(): void
    {
        foreach (self::ICONS as $slug => $icon) {
            Category::where('slug', $slug)->update(['icon' => $icon]);
        }
    }
}
